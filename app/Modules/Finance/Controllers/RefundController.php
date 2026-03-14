<?php

namespace App\Modules\Finance\Controllers;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Finance\Models\CaseRefund;
use App\Modules\Finance\Policies\FinancePolicy;
use App\Modules\Finance\Services\ContractService;
use App\Support\Helpers\ApiResponse;
use App\Support\Helpers\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RefundController extends Controller
{
    /**
     * GET /cases/{caseId}/refunds — список возвратов по заявке.
     */
    public function index(string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);

        $refunds = CaseRefund::where('case_id', $caseId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'amount'        => $r->amount,
                'currency'      => $r->currency,
                'reason'        => $r->reason,
                'type'          => $r->type,
                'basis'         => $r->basis,
                'initiator'     => $r->initiator,
                'status'        => $r->status,
                'refund_method' => $r->refund_method,
                'approved_by'   => $r->approver?->name,
                'approved_at'   => $r->approved_at?->toDateTimeString(),
                'completed_at'  => $r->completed_at?->toDateTimeString(),
                'comment'       => $r->comment,
                'created_at'    => $r->created_at?->toDateTimeString(),
            ]);

        return ApiResponse::success(['refunds' => $refunds]);
    }

    /**
     * POST /cases/{caseId}/refunds — создать запрос на возврат.
     */
    public function store(Request $request, string $caseId): JsonResponse
    {
        $case = VisaCase::findOrFail($caseId);

        $policy = app(FinancePolicy::class);
        if (!$policy->createRefund($request->user(), $case)) {
            return ApiResponse::forbidden('Недостаточно прав для создания возврата.');
        }

        $data = $request->validate([
            'amount'        => 'required|integer|min:1',
            'reason'        => 'required|string|max:500',
            'type'          => ['required', Rule::in(['full', 'partial'])],
            'basis'         => ['sometimes', Rule::in(['contract_policy', 'individual_decision', 'visa_rejection'])],
            'refund_method' => ['sometimes', 'nullable', Rule::in(['cash', 'terminal', 'bank_transfer', 'payme', 'click', 'uzum', 'other'])],
            'comment'       => 'sometimes|nullable|string|max:500',
        ]);

        $paidAmount = $case->paidAmount();
        if ($data['amount'] > $paidAmount) {
            return ApiResponse::error('Сумма возврата не может превышать оплаченную сумму', null, 422);
        }

        $refund = CaseRefund::create([
            'case_id'       => $case->id,
            'agency_id'     => $case->agency_id,
            'amount'        => $data['amount'],
            'currency'      => $case->price_currency ?? 'UZS',
            'reason'        => $data['reason'],
            'type'          => $data['type'],
            'basis'         => $data['basis'] ?? 'contract_policy',
            'initiator'     => 'agent',
            'status'        => 'approved',
            'approved_by'   => Auth::id(),
            'approved_at'   => now(),
            'refund_method' => $data['refund_method'] ?? null,
            'comment'       => $data['comment'] ?? null,
            'created_by'    => Auth::id(),
        ]);

        // Записать в case
        $case->update(['refund_amount' => ($case->refund_amount ?? 0) + $data['amount']]);

        \App\Modules\Case\Services\CaseService::logActivity(
            $case, 'refund_created',
            "Возврат: " . number_format($data['amount']) . " " . ($case->price_currency ?? 'UZS') . " — " . $data['reason'],
            ['refund_id' => $refund->id, 'amount' => $data['amount']],
            true
        );

        AuditLog::log('finance.refund_created', [
            'case_id' => $case->id, 'refund_id' => $refund->id,
            'amount' => $data['amount'], 'reason' => $data['reason'],
            'user_id' => Auth::id(),
        ]);

        return ApiResponse::success(['refund' => $refund], 'Возврат оформлен');
    }

    /**
     * PATCH /cases/{caseId}/refunds/{refundId}/complete — отметить возврат выполненным.
     */
    public function complete(string $caseId, string $refundId): JsonResponse
    {
        $refund = CaseRefund::where('case_id', $caseId)->findOrFail($refundId);

        $refund->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return ApiResponse::success(['refund' => $refund], 'Возврат выполнен');
    }
}
