<?php

namespace App\Modules\Finance\Controllers;

use App\Modules\Case\Models\CasePayment;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Finance\Policies\FinancePolicy;
use App\Modules\Finance\Services\CasePaymentService;
use App\Support\Helpers\ApiResponse;
use App\Support\Helpers\AuditLog;
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

        $policy = app(FinancePolicy::class);
        if (!$policy->recordPayment($request->user(), $case)) {
            return ApiResponse::forbidden('Недостаточно прав для записи оплаты.');
        }

        $data = $request->validate([
            'amount'         => 'required|integer|min:1',
            'payment_method' => ['required', Rule::in(['cash', 'terminal', 'bank_transfer', 'payme', 'click', 'uzum', 'other'])],
            'paid_at'        => 'sometimes|date',
            'currency'       => 'sometimes|string|size:3',
            'comment'        => 'sometimes|nullable|string|max:500',
        ]);

        $payment = $this->service->recordPayment($case, $data);

        AuditLog::log('finance.payment_recorded', [
            'case_id' => $case->id, 'amount' => $data['amount'],
            'method' => $data['payment_method'], 'user_id' => $request->user()->id,
        ]);

        return ApiResponse::success([
            'payment' => $payment,
            'summary' => $this->service->getPaymentSummary($case->fresh()),
        ], 'Оплата записана');
    }

    /**
     * DELETE /cases/{caseId}/payments/{paymentId} — удалить платёж.
     */
    public function destroy(Request $request, string $caseId, string $paymentId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);
        $policy = app(FinancePolicy::class);
        if (!$policy->deletePayment($request->user(), $case)) {
            return ApiResponse::forbidden('Только руководитель может удалять платежи.');
        }

        $payment = CasePayment::where('case_id', $caseId)->findOrFail($paymentId);

        AuditLog::log('finance.payment_deleted', [
            'case_id' => $caseId, 'payment_id' => $paymentId,
            'amount' => $payment->amount, 'user_id' => $request->user()->id,
        ]);

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

        $policy = app(FinancePolicy::class);
        if (!$policy->updatePaymentSettings($request->user(), $case)) {
            return ApiResponse::forbidden('Недостаточно прав.');
        }

        // Проверка: нельзя менять стоимость подписанного договора
        $activeContract = $case->activeContract;
        if ($activeContract && $activeContract->isLocked() && isset($request->total_price)) {
            return ApiResponse::error('Нельзя изменить стоимость подписанного договора. Создайте допсоглашение.', null, 422);
        }

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

        AuditLog::log('finance.settings_updated', [
            'case_id' => $case->id, 'changes' => $data, 'user_id' => $request->user()->id,
        ]);

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
        $contractService = app(\App\Modules\Finance\Services\ContractService::class);

        return ApiResponse::success($contractService->getContractData($case));
    }

    /**
     * POST /cases/{caseId}/contract/accept — зафиксировать принятие договора.
     */
    public function acceptContract(Request $request, string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);
        $contractService = app(\App\Modules\Finance\Services\ContractService::class);

        $case = $contractService->acceptContract($case);

        AuditLog::log('finance.contract_accepted', [
            'case_id' => $case->id, 'contract_number' => $case->contract_number,
            'user_id' => $request->user()?->id,
        ]);

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
        $contractService = app(\App\Modules\Finance\Services\ContractService::class);

        return ApiResponse::success($contractService->calculateRefund($case));
    }
}
