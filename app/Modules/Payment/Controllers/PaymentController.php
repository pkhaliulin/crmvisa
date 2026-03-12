<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\Gateways\ClickGateway;
use App\Modules\Payment\Services\Gateways\PaymeGateway;
use App\Modules\Payment\Services\Gateways\UzumGateway;
use App\Modules\Payment\Services\PaymentGateway;
use App\Modules\Payment\Services\PaymentProcessingService;
use App\Modules\Payment\Services\SubscriptionService;
use App\Support\Helpers\ApiResponse;
use App\Support\Helpers\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly SubscriptionService       $subscriptionService,
        private readonly PaymentProcessingService  $paymentProcessing,
    ) {}

    /**
     * POST /api/v1/payments/create
     * Создать платёж для подписки.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:click,payme,uzum',
            'type'     => 'nullable|string|in:subscription,activation_fee',
        ]);

        $agency       = $request->user()->agency;
        $subscription = $this->subscriptionService->activeSubscription($agency);

        if (! $subscription) {
            return ApiResponse::error('Нет активной подписки', null, 422);
        }

        $type = $validated['type'] ?? 'subscription';
        $plan = $subscription->plan;

        // Определить сумму
        if ($type === 'activation_fee') {
            if ($subscription->activation_fee_paid || $plan->activation_fee_uzs <= 0) {
                return ApiResponse::error('Активационный сбор не требуется или уже оплачен', null, 422);
            }
            $amount = (float) $plan->activation_fee_uzs;
        } else {
            $price  = $subscription->billing_period === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
            $amount = (float) max(0, $price - ($subscription->discount_amount ?? 0));
        }

        if ($amount <= 0) {
            return ApiResponse::error('Сумма к оплате равна нулю', null, 422);
        }

        // Проверить нет ли уже pending-платежа
        $existingPending = Payment::where('agency_id', $agency->id)
            ->where('status', 'pending')
            ->where('provider', $validated['provider'])
            ->where('created_at', '>=', now()->subHours(2))
            ->first();

        if ($existingPending) {
            $gateway     = $this->resolveGateway($validated['provider']);
            $paymentData = $gateway->createPayment($amount, $existingPending->id, [
                'return_url'  => config('app.url') . '/billing?payment=pending',
                'description' => $type === 'activation_fee'
                    ? "Активационный сбор: {$plan->name}"
                    : "Подписка: {$plan->name} ({$subscription->billing_period})",
            ]);

            return ApiResponse::success([
                'payment_id'   => $existingPending->id,
                'checkout_url' => $paymentData['checkout_url'],
                'amount'       => $amount,
                'provider'     => $validated['provider'],
            ]);
        }

        // Создать новый платёж
        $payment = Payment::create([
            'agency_id'       => $agency->id,
            'subscription_id' => $subscription->id,
            'provider'        => $validated['provider'],
            'amount'          => $amount,
            'currency'        => 'UZS',
            'status'          => 'pending',
            'metadata'        => [
                'type'            => $type,
                'plan_slug'       => $subscription->plan_slug,
                'billing_period'  => $subscription->billing_period,
                'initiated_by'    => $request->user()->id,
            ],
        ]);

        $gateway     = $this->resolveGateway($validated['provider']);
        $paymentData = $gateway->createPayment($amount, $payment->id, [
            'return_url'  => config('app.url') . '/billing?payment=pending',
            'description' => $type === 'activation_fee'
                ? "Активационный сбор: {$plan->name}"
                : "Подписка: {$plan->name} ({$subscription->billing_period})",
        ]);

        if (! empty($paymentData['external_id'])) {
            $payment->update(['external_id' => $paymentData['external_id']]);
        }

        AuditLog::log('payment.created', [
            'payment_id' => $payment->id,
            'provider'   => $validated['provider'],
            'amount'     => $amount,
            'type'       => $type,
        ]);

        Log::channel('billing')->info('Subscription payment created', [
            'payment_id'  => $payment->id,
            'agency_id'   => $agency->id,
            'provider'    => $validated['provider'],
            'amount'      => $amount,
            'type'        => $type,
        ]);

        return ApiResponse::success([
            'payment_id'   => $payment->id,
            'checkout_url' => $paymentData['checkout_url'],
            'amount'       => $amount,
            'provider'     => $validated['provider'],
        ]);
    }

    /**
     * GET /api/v1/payments/{id}/status
     */
    public function status(Request $request, string $id): JsonResponse
    {
        $agency  = $request->user()->agency;
        $payment = Payment::where('agency_id', $agency->id)->findOrFail($id);

        return ApiResponse::success([
            'id'            => $payment->id,
            'status'        => $payment->status,
            'provider'      => $payment->provider,
            'amount'        => $payment->amount,
            'currency'      => $payment->currency,
            'external_id'   => $payment->external_id,
            'paid_at'       => $payment->paid_at?->toDateTimeString(),
            'error_message' => $payment->error_message,
            'created_at'    => $payment->created_at->toDateTimeString(),
        ]);
    }

    /**
     * GET /api/v1/payments
     * Список платежей агентства.
     */
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $payments = Payment::where('agency_id', $agency->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::paginated($payments);
    }

    /**
     * POST /api/v1/payments/click/callback
     * Webhook от Click (без авторизации, проверка sign).
     */
    public function clickCallback(Request $request): JsonResponse
    {
        Log::channel('billing')->info('Click subscription webhook received', [
            'ip'      => $request->ip(),
            'payload' => $request->except(['sign_string']),
        ]);

        $gateway = new ClickGateway();
        $result  = $gateway->handleCallback($request->all());

        $orderId = $result['order_id'] ?? null;
        $payment = $orderId ? Payment::find($orderId) : null;

        if (! $payment) {
            Log::channel('billing')->warning('Click callback: payment not found', ['order_id' => $orderId]);
            return response()->json(ClickGateway::formatResponse(
                -5, 'Order not found', $result['external_id'] ?? '', $orderId ?? ''
            ));
        }

        $action = $result['action'] ?? null;

        if (! $result['success']) {
            $payment->markFailed($result['error'] ?? 'Click error', $result['external_id'] ?? null);
            return response()->json(ClickGateway::formatResponse(
                -1, $result['error'] ?? 'Error', $result['external_id'] ?? '', $orderId
            ));
        }

        if ($action === 'prepare') {
            $payment->update(['status' => 'processing', 'external_id' => $result['external_id'] ?? $payment->external_id]);
            return response()->json(ClickGateway::formatResponse(
                0, 'Success', $result['external_id'] ?? '', $orderId, $payment->id
            ));
        }

        if ($action === 'complete') {
            $this->completeSubscriptionPayment($payment, $result['external_id'] ?? '');
            return response()->json(ClickGateway::formatResponse(
                0, 'Success', $result['external_id'] ?? '', $orderId, $payment->id
            ));
        }

        return response()->json(ClickGateway::formatResponse(
            -3, 'Unknown action', $result['external_id'] ?? '', $orderId
        ));
    }

    /**
     * POST /api/v1/payments/payme/callback
     * Webhook от Payme (JSON-RPC, проверка Basic auth).
     */
    public function paymeCallback(Request $request): JsonResponse
    {
        Log::channel('billing')->info('Payme subscription webhook received', [
            'ip'     => $request->ip(),
            'method' => $request->input('method'),
        ]);

        // Проверка Basic auth
        $authHeader = $request->header('Authorization', '');
        if (! PaymeGateway::verifyAuth($authHeader)) {
            Log::channel('billing')->warning('Payme callback: auth failed', ['ip' => $request->ip()]);
            return response()->json(PaymeGateway::errorResponse(
                $request->input('id'), -32504, 'Unauthorized'
            ));
        }

        $gateway = new PaymeGateway();
        $result  = $gateway->handleCallback($request->all());

        $method  = $result['method'] ?? '';
        $orderId = $result['order_id'] ?? null;

        // Для PerformTransaction — нужно найти payment по external_id (Payme transaction ID)
        if ($method === 'PerformTransaction' && $result['success']) {
            $payment = Payment::where('external_id', $result['external_id'])->first();
            if ($payment && $payment->isPending()) {
                $this->completeSubscriptionPayment($payment, $result['external_id']);
            }
        }

        // Для CreateTransaction — привязать external_id к нашему payment
        if ($method === 'CreateTransaction' && $orderId) {
            $payment = Payment::find($orderId);
            if ($payment) {
                $payment->update([
                    'status'      => 'processing',
                    'external_id' => $result['external_id'] ?? $payment->external_id,
                ]);
            }
        }

        // Для CancelTransaction — отменить платёж
        if ($method === 'CancelTransaction') {
            $payment = Payment::where('external_id', $result['external_id'])->first();
            if ($payment && ! $payment->isCompleted()) {
                $payment->markFailed($result['error'] ?? 'Cancelled by Payme', $result['external_id']);
            }
        }

        return response()->json($result['jsonrpc_response'] ?? ['ok' => true]);
    }

    /**
     * POST /api/v1/payments/uzum/callback
     * Webhook от Uzum (проверка signature).
     */
    public function uzumCallback(Request $request): JsonResponse
    {
        Log::channel('billing')->info('Uzum subscription webhook received', [
            'ip'      => $request->ip(),
            'payload' => $request->except(['sign']),
        ]);

        // Проверка подписи
        if (! UzumGateway::verifySignature($request->all())) {
            Log::channel('billing')->warning('Uzum callback: signature failed', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $gateway = new UzumGateway();
        $result  = $gateway->handleCallback($request->all());

        $orderId = $result['order_id'] ?? null;
        $payment = $orderId ? Payment::find($orderId) : null;

        if (! $payment && $result['external_id']) {
            $payment = Payment::where('external_id', $result['external_id'])->first();
        }

        if (! $payment) {
            Log::channel('billing')->warning('Uzum callback: payment not found', [
                'order_id'    => $orderId,
                'external_id' => $result['external_id'] ?? null,
            ]);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($result['success']) {
            $this->completeSubscriptionPayment($payment, $result['external_id'] ?? '');
        } else {
            $payment->markFailed($result['error'] ?? 'Uzum payment failed', $result['external_id'] ?? null);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /**
     * Завершить оплату подписки: обновить Payment + вызвать PaymentProcessingService.
     */
    private function completeSubscriptionPayment(Payment $payment, string $externalId): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        DB::transaction(function () use ($payment, $externalId) {
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if (! $locked || $locked->isCompleted()) {
                return;
            }

            $locked->markCompleted($externalId);

            $type = $locked->metadata['type'] ?? 'subscription';

            try {
                $agency = $locked->agency;
                if (! $agency) {
                    return;
                }

                if ($type === 'activation_fee') {
                    $this->paymentProcessing->payActivationFee(
                        $agency,
                        $locked->provider,
                        $externalId,
                    );
                } else {
                    $this->paymentProcessing->paySubscription(
                        $agency,
                        $locked->provider,
                        $externalId,
                    );
                }
            } catch (\Throwable $e) {
                Log::channel('billing')->error('Failed to process subscription payment', [
                    'payment_id' => $locked->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            AuditLog::log('payment.completed', [
                'payment_id'  => $locked->id,
                'provider'    => $locked->provider,
                'amount'      => $locked->amount,
                'external_id' => $externalId,
                'type'        => $type,
            ]);

            Log::channel('billing')->info('Subscription payment completed', [
                'payment_id'  => $locked->id,
                'agency_id'   => $locked->agency_id,
                'provider'    => $locked->provider,
                'amount'      => $locked->amount,
                'type'        => $type,
            ]);
        });
    }

    /**
     * Получить экземпляр gateway по имени провайдера.
     */
    private function resolveGateway(string $provider): PaymentGateway
    {
        return match ($provider) {
            'click' => new ClickGateway(),
            'payme' => new PaymeGateway(),
            'uzum'  => new UzumGateway(),
            default => throw new \InvalidArgumentException("Unknown payment provider: {$provider}"),
        };
    }
}
