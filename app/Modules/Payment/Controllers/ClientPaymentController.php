<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Payment\Jobs\ProcessWebhookJob;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\Payment\Services\ClientPaymentService;
use App\Support\Helpers\ApiResponse;
use App\Support\Helpers\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientPaymentController extends Controller
{
    public function __construct(
        private ClientPaymentService $paymentService,
    ) {}

    /**
     * POST /public/me/payments/initiate
     * Инициировать оплату.
     */
    public function initiate(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $data = $request->validate([
            'case_id'  => ['required', 'uuid', 'exists:cases,id'],
            'provider' => ['required', 'string', 'in:click,payme,uzum'],
        ]);

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->where('public_status', 'awaiting_payment')
            ->findOrFail($data['case_id']);

        if ($case->payment_status === 'paid') {
            return ApiResponse::error('Услуга уже оплачена.', null, 422);
        }

        // Если уже есть pending-платёж — вернуть его URL
        $existing = ClientPayment::where('case_id', $case->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return ApiResponse::success([
                'payment_id'  => $existing->id,
                'payment_url' => $this->paymentService->getPaymentUrl($existing),
            ]);
        }

        $payment = $this->paymentService->createPayment($case, $publicUser, $data['provider']);

        \App\Support\Helpers\AuditLog::log('payment.initiated', [
            'payment_id' => $payment->id,
            'case_id' => $case->id,
            'provider' => $data['provider'],
            'public_user_id' => $publicUser->id,
        ]);

        return ApiResponse::success([
            'payment_id'  => $payment->id,
            'payment_url' => $this->paymentService->getPaymentUrl($payment),
        ]);
    }

    /**
     * POST /public/payments/callback/{provider}
     * Webhook от Click/Payme/Uzum (без авторизации).
     */
    public function callback(string $provider, Request $request): JsonResponse
    {
        if (! in_array($provider, ['click', 'payme', 'uzum', 'test'])) {
            return response()->json(['error' => 'Unknown provider'], 400);
        }

        // Тестовый провайдер запрещён если явно отключено
        if ($provider === 'test' && config('services.payments.disable_test', false)) {
            Log::channel('billing')->warning('Test webhook rejected (disabled)', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Test provider not allowed'], 403);
        }

        // Валидация подписи webhook
        if ($provider !== 'test' && ! $this->validateWebhookSignature($provider, $request)) {
            Log::channel('billing')->warning('Webhook signature validation failed', [
                'provider' => $provider,
                'ip'       => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Логирование входящего webhook
        $sanitizedPayload = $request->except(['sign_string', 'auth_header']);
        Log::channel('billing')->info('Webhook received', [
            'provider'       => $provider,
            'transaction_id' => $request->input('provider_transaction_id') ?? $request->input('transaction_id'),
            'amount'         => $request->input('amount'),
            'payload'        => $sanitizedPayload,
        ]);

        AuditLog::log('payment.webhook_received', [
            'provider'       => $provider,
            'transaction_id' => $request->input('provider_transaction_id') ?? $request->input('transaction_id'),
            'amount'         => $request->input('amount'),
            'ip'             => $request->ip(),
        ]);

        // Асинхронная обработка через Job (#23) — контроллер сразу возвращает 200
        ProcessWebhookJob::dispatch($provider, $request->all());

        return response()->json(['ok' => true]);
    }

    /**
     * Валидация подписи webhook от платёжных провайдеров.
     */
    private function validateWebhookSignature(string $provider, Request $request): bool
    {
        return match ($provider) {
            'payme' => $this->validatePaymeSignature($request),
            'click' => $this->validateClickSignature($request),
            default => true, // uzum и другие — пока без валидации
        };
    }

    /**
     * Payme: проверка Authorization header (Basic base64(merchant_id:key)).
     */
    private function validatePaymeSignature(Request $request): bool
    {
        $merchantId  = config('services.payme.merchant_id');
        $merchantKey = config('services.payme.merchant_key');

        // Если ключи не настроены — пропускаем (dev/staging)
        if (empty($merchantId) || empty($merchantKey)) {
            return ! config('services.payments.disable_test', false);
        }

        $authHeader = $request->header('Authorization', '');

        if (! str_starts_with($authHeader, 'Basic ')) {
            return false;
        }

        $decoded  = base64_decode(substr($authHeader, 6));
        $expected = $merchantId . ':' . $merchantKey;

        return hash_equals($expected, $decoded);
    }

    /**
     * Click: проверка sign_string (MD5 hash).
     * sign_string = md5(click_trans_id + service_id + secret_key + merchant_trans_id + amount + action + sign_time)
     */
    private function validateClickSignature(Request $request): bool
    {
        $secretKey = config('services.click.secret_key');
        $serviceId = config('services.click.service_id');

        // Если ключи не настроены — пропускаем (dev/staging)
        if (empty($secretKey) || empty($serviceId)) {
            return ! config('services.payments.disable_test', false);
        }

        $signString = $request->input('sign_string');
        if (empty($signString)) {
            return false;
        }

        $expected = md5(
            $request->input('click_trans_id') .
            $serviceId .
            $secretKey .
            $request->input('merchant_trans_id') .
            $request->input('amount') .
            $request->input('action') .
            $request->input('sign_time')
        );

        return hash_equals($expected, $signString);
    }

    /**
     * GET /public/me/cases/{id}/payment
     * Статус оплаты по кейсу.
     */
    public function status(Request $request, string $caseId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($caseId);

        $payment = ClientPayment::where('case_id', $case->id)
            ->orderByDesc('created_at')
            ->first();

        // Пересчёт при просмотре (catch-up для платежей без breakdown)
        if ($payment && $payment->status === 'pending' && empty($payment->metadata['price_breakdown'])) {
            ClientPaymentService::recalculatePaymentAmount($payment);
            $payment->refresh();
        }

        return ApiResponse::success([
            'payment_status' => $case->payment_status ?? 'unpaid',
            'payment'        => $payment ? [
                'id'              => $payment->id,
                'amount'          => $payment->amount,
                'currency'        => $payment->currency,
                'provider'        => $payment->provider,
                'status'          => $payment->status,
                'paid_at'         => $payment->paid_at?->toDateTimeString(),
                'price_breakdown' => $payment->metadata['price_breakdown'] ?? null,
                'base_price'      => $payment->metadata['base_price'] ?? null,
            ] : null,
        ]);
    }

    /**
     * POST /public/me/payments/mark-paid
     * Тестовая заглушка — отметить как оплачено (имитация callback).
     */
    public function markAsPaid(Request $request): JsonResponse
    {
        // Запрет тестовой оплаты если явно отключено (по умолчанию разрешено)
        if (config('services.payments.disable_test', false)) {
            return ApiResponse::error('Тестовая оплата отключена', null, 403);
        }

        $publicUser = $request->get('_public_user');

        // RLS superadmin для доступа к cases/clients (кроссирующие tenant)
        \DB::statement("SET LOCAL app.is_superadmin = 'true'");

        $data = $request->validate([
            'case_id' => ['required', 'uuid'],
        ]);

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->where('public_status', 'awaiting_payment')
            ->findOrFail($data['case_id']);

        // Найти или создать pending-платёж
        $payment = ClientPayment::where('case_id', $case->id)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            $payment = $this->paymentService->createPayment($case, $publicUser, 'test');
        }

        // Имитация callback
        $this->paymentService->handleCallback('test', [
            'payment_id' => $payment->id,
            'provider_transaction_id' => 'TEST-' . now()->timestamp,
        ]);

        \App\Support\Helpers\AuditLog::log('payment.marked_paid', [
            'payment_id' => $payment->id,
            'case_id' => $case->id,
            'public_user_id' => $publicUser->id,
        ]);

        return ApiResponse::success([
            'message' => 'Оплата отмечена как выполненная (тестовый режим)',
        ]);
    }

    /**
     * GET /public/me/billing
     * История оплат клиента.
     */
    public function history(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        // Временно разрешить чтение кейсов через RLS (платежи уже фильтруются по public_user_id)
        \DB::statement("SET LOCAL app.is_superadmin = 'true'");

        $payments = ClientPayment::where('public_user_id', $publicUser->id)
            ->with([
                'case:id,case_number,country_code,visa_type,public_status,client_id,group_id,critical_date',
                'case.client:id,name',
                'agency:id,name,city,logo_url',
                'package:id,name,description,processing_days',
                'package.items.service:id,name,category',
                'group:id,name',
                'group.members:id,group_id,name',
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        \DB::statement("RESET app.is_superadmin");

        // Пересчёт pending-платежей с семьёй но без breakdown (catch-up)
        foreach ($payments->getCollection() as $p) {
            if ($p->status === 'pending' && $p->case_id && empty($p->metadata['price_breakdown'])) {
                $hasFam = \DB::table('case_family_members')->where('case_id', $p->case_id)->exists();
                if ($hasFam) {
                    ClientPaymentService::recalculatePaymentAmount($p);
                    $p->refresh();
                }
            }
        }

        $items = $payments->getCollection()->map(function ($p) {
            // Заявитель
            $applicantName = $p->case?->client?->name;

            // Члены семьи из case
            $familyMembers = [];
            if ($p->case_id) {
                try {
                    $familyMembers = \DB::table('case_family_members')
                        ->join('public_user_family_members', 'case_family_members.family_member_id', '=', 'public_user_family_members.id')
                        ->where('case_family_members.case_id', $p->case_id)
                        ->select('public_user_family_members.name', 'public_user_family_members.relationship')
                        ->get()
                        ->map(fn ($fm) => ['name' => $fm->name, 'relationship' => $fm->relationship])
                        ->toArray();
                } catch (\Throwable) {
                    $familyMembers = [];
                }
            }

            // Группа
            $groupMembers = [];
            if ($p->group_id && $p->group) {
                $groupMembers = $p->group->members->map(fn ($m) => ['name' => $m->name])->toArray();
            }

            $totalPersons = 1 + count($familyMembers) + count($groupMembers);

            return [
                'id'              => $p->id,
                'amount'          => $p->amount,
                'currency'        => $p->currency,
                'provider'        => $p->provider,
                'status'          => $p->status,
                'paid_at'         => $p->paid_at?->toDateTimeString(),
                'created_at'      => $p->created_at->toDateTimeString(),
                'country_code'    => $p->case?->country_code,
                'visa_type'       => $p->case?->visa_type,
                'case_number'     => $p->case?->case_number,
                'case_id'         => $p->case_id,
                'case_status'     => $p->case?->public_status,
                'agency_name'     => $p->agency?->name,
                'agency_city'     => $p->agency?->city,
                'agency_logo'     => $p->agency?->logo_url,
                'package_name'    => $p->package?->name,
                'package_desc'    => $p->package?->description,
                'package_days'    => $p->package?->processing_days,
                'services'        => $p->package?->items?->map(fn ($item) => [
                    'name'     => $item->service?->name ?? '',
                    'category' => $item->service?->category ?? '',
                ])->filter(fn ($s) => $s['name'])->values() ?? [],
                'applicant_name'  => $applicantName,
                'family_members'  => $familyMembers,
                'group_id'        => $p->group_id,
                'group_name'      => $p->group?->name,
                'group_members'   => $groupMembers,
                'total_persons'   => $totalPersons,
                'critical_date'   => $p->case?->critical_date?->toDateString(),
                'expires_at'      => $p->expires_at?->toDateTimeString(),
                'price_breakdown' => $p->metadata['price_breakdown'] ?? null,
                'base_price'      => $p->metadata['base_price'] ?? null,
            ];
        });

        return ApiResponse::success([
            'payments' => $items,
            'total'    => $payments->total(),
            'page'     => $payments->currentPage(),
            'pages'    => $payments->lastPage(),
        ]);
    }
}
