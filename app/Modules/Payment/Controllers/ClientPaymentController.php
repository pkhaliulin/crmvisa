<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\Payment\Services\ClientPaymentService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        if (! in_array($provider, ['click', 'payme', 'uzum'])) {
            return response()->json(['error' => 'Unknown provider'], 400);
        }

        $this->paymentService->handleCallback($provider, $request->all());

        return response()->json(['ok' => true]);
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

        return ApiResponse::success([
            'payment_status' => $case->payment_status ?? 'unpaid',
            'payment'        => $payment ? [
                'id'       => $payment->id,
                'amount'   => $payment->amount,
                'currency' => $payment->currency,
                'provider' => $payment->provider,
                'status'   => $payment->status,
                'paid_at'  => $payment->paid_at?->toDateTimeString(),
            ] : null,
        ]);
    }

    /**
     * POST /public/me/payments/mark-paid
     * Тестовая заглушка — отметить как оплачено (имитация callback).
     */
    public function markAsPaid(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $data = $request->validate([
            'case_id' => ['required', 'uuid', 'exists:cases,id'],
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

        $payments = ClientPayment::where('public_user_id', $publicUser->id)
            ->with([
                'case:id,case_number,country_code,visa_type,public_status',
                'agency:id,name,city,logo_url',
                'package:id,name,description,processing_days',
                'package.items.service:id,name,category',
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        $items = $payments->getCollection()->map(fn ($p) => [
            'id'            => $p->id,
            'amount'        => $p->amount,
            'currency'      => $p->currency,
            'provider'      => $p->provider,
            'status'        => $p->status,
            'paid_at'       => $p->paid_at?->toDateTimeString(),
            'created_at'    => $p->created_at->toDateTimeString(),
            'country_code'  => $p->case?->country_code,
            'visa_type'     => $p->case?->visa_type,
            'case_number'   => $p->case?->case_number,
            'case_id'       => $p->case_id,
            'case_status'   => $p->case?->public_status,
            'agency_name'   => $p->agency?->name,
            'agency_city'   => $p->agency?->city,
            'agency_logo'   => $p->agency?->logo_url,
            'package_name'  => $p->package?->name,
            'package_desc'  => $p->package?->description,
            'package_days'  => $p->package?->processing_days,
            'services'      => $p->package?->items?->map(fn ($item) => [
                'name'     => $item->service?->name ?? '',
                'category' => $item->service?->category ?? '',
            ])->filter(fn ($s) => $s['name'])->values() ?? [],
        ]);

        return ApiResponse::success([
            'payments' => $items,
            'total'    => $payments->total(),
            'page'     => $payments->currentPage(),
            'pages'    => $payments->lastPage(),
        ]);
    }
}
