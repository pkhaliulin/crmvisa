<?php

namespace App\Modules\Case\Controllers;

use App\Modules\Case\Models\CasePayment;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CasePaymentService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class CasePaymentController extends Controller
{
    public function __construct(private CasePaymentService $service) {}

    /**
     * GET /cases/{caseId}/payments — список платежей + сводка.
     */
    public function index(string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);

        return ApiResponse::success($this->service->getPaymentSummary($case));
    }

    /**
     * POST /cases/{caseId}/payments — записать платёж.
     */
    public function store(Request $request, string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);

        $data = $request->validate([
            'amount'         => 'required|integer|min:1',
            'payment_method' => ['required', Rule::in(['cash', 'terminal', 'bank_transfer', 'payme', 'click', 'uzum', 'other'])],
            'paid_at'        => 'sometimes|date',
            'currency'       => 'sometimes|string|size:3',
            'comment'        => 'sometimes|nullable|string|max:500',
        ]);

        $payment = $this->service->recordPayment($case, $data);

        return ApiResponse::success([
            'payment' => $payment,
            'summary' => $this->service->getPaymentSummary($case->fresh()),
        ], 'Оплата записана');
    }

    /**
     * DELETE /cases/{caseId}/payments/{paymentId} — удалить платёж.
     */
    public function destroy(string $caseId, string $paymentId): JsonResponse
    {
        $payment = CasePayment::where('case_id', $caseId)->findOrFail($paymentId);

        $this->service->deletePayment($payment);

        return ApiResponse::success(
            $this->service->getPaymentSummary(VisaCase::findOrFail($caseId)),
            'Оплата удалена'
        );
    }

    /**
     * PATCH /cases/{caseId}/payment-settings — обновить стоимость / дедлайн.
     */
    public function updateSettings(Request $request, string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);

        $data = $request->validate([
            'total_price'      => 'sometimes|integer|min:0',
            'price_currency'   => 'sometimes|string|size:3',
            'payment_deadline' => 'sometimes|nullable|date',
        ]);

        if (isset($data['total_price'])) {
            $this->service->setTotalPrice($case, $data['total_price'], $data['price_currency'] ?? $case->price_currency ?? 'UZS');
        }

        if (array_key_exists('payment_deadline', $data)) {
            $this->service->setPaymentDeadline($case, $data['payment_deadline']);
        }

        return ApiResponse::success(
            $this->service->getPaymentSummary($case->fresh()),
            'Настройки оплаты обновлены'
        );
    }

    /**
     * GET /cases/{caseId}/invoice — данные для печатного счёта.
     */
    public function invoice(string $caseId): JsonResponse
    {
        $case = VisaCase::with(['client', 'agency'])->findOrFail($caseId);

        $summary = $this->service->getPaymentSummary($case);

        return ApiResponse::success([
            'invoice' => [
                'case_number'    => $case->case_number,
                'date'           => now()->toDateString(),
                'agency'         => [
                    'name'    => $case->agency->name ?? '',
                    'phone'   => $case->agency->phone ?? '',
                    'address' => $case->agency->address ?? '',
                ],
                'client'         => [
                    'name'  => $case->client->name ?? '',
                    'phone' => $case->client->phone ?? '',
                ],
                'service'        => $case->visa_type . ' — ' . $case->country_code,
                'total_price'    => $summary['total_price'],
                'paid_amount'    => $summary['paid_amount'],
                'remaining'      => $summary['remaining_balance'],
                'currency'       => $summary['price_currency'],
                'payment_status' => $summary['payment_status'],
                'payments'       => $summary['payments'],
            ],
        ]);
    }

    /**
     * GET /cases/{caseId}/contract — данные договора для печати.
     */
    public function contract(string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);
        $contractService = app(\App\Modules\Case\Services\ContractService::class);

        return ApiResponse::success($contractService->getContractData($case));
    }

    /**
     * POST /cases/{caseId}/contract/accept — зафиксировать принятие договора.
     */
    public function acceptContract(string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);
        $contractService = app(\App\Modules\Case\Services\ContractService::class);

        $case = $contractService->acceptContract($case);

        return ApiResponse::success([
            'contract_number'      => $case->contract_number,
            'contract_accepted_at' => $case->contract_accepted_at?->toDateTimeString(),
        ], 'Договор принят');
    }

    /**
     * GET /cases/{caseId}/refund-preview — предварительный расчёт возврата при отмене.
     */
    public function refundPreview(string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);
        $contractService = app(\App\Modules\Case\Services\ContractService::class);

        return ApiResponse::success($contractService->calculateRefund($case));
    }
}
