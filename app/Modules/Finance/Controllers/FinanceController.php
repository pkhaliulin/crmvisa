<?php

namespace App\Modules\Finance\Controllers;

use App\Modules\Case\Models\CasePayment;
use App\Modules\Finance\Models\CaseContract;
use App\Modules\Finance\Models\CaseRefund;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * GET /finance/overview — сводка по финансам агентства.
     */
    public function overview(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $payments = DB::table('case_payments')
            ->where('agency_id', $agencyId)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_payments,
                COALESCE(SUM(amount), 0) as total_received,
                COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END), 0) as cash_total,
                COALESCE(SUM(CASE WHEN payment_method = 'terminal' THEN amount ELSE 0 END), 0) as terminal_total,
                COALESCE(SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END), 0) as bank_transfer_total,
                COALESCE(SUM(CASE WHEN payment_method IN ('payme','click','uzum') THEN amount ELSE 0 END), 0) as online_total
            ")
            ->first();

        $cases = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_cases,
                COALESCE(SUM(total_price), 0) as total_contract_value,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END), 0) as fully_paid,
                COALESCE(SUM(CASE WHEN payment_status = 'prepayment' THEN 1 ELSE 0 END), 0) as partial_paid,
                COALESCE(SUM(CASE WHEN payment_status = 'unpaid' AND total_price > 0 THEN 1 ELSE 0 END), 0) as unpaid,
                COALESCE(SUM(CASE WHEN public_status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled,
                COALESCE(SUM(CASE WHEN payment_blocked = true THEN 1 ELSE 0 END), 0) as blocked
            ")
            ->first();

        $refunds = DB::table('case_refunds')
            ->where('agency_id', $agencyId)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_refunds,
                COALESCE(SUM(amount), 0) as total_refunded,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_refunds,
                COALESCE(SUM(CASE WHEN status IN ('draft','requested','reviewing','approved') THEN amount ELSE 0 END), 0) as pending_refunds
            ")
            ->first();

        // Задолженности (заявки с остатком)
        $debts = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->whereNull('deleted_at')
            ->where('total_price', '>', 0)
            ->whereIn('payment_status', ['unpaid', 'prepayment'])
            ->whereNotIn('public_status', ['cancelled', 'draft'])
            ->selectRaw("COALESCE(SUM(total_price), 0) - (
                SELECT COALESCE(SUM(cp.amount), 0) FROM case_payments cp
                WHERE cp.agency_id = cases.agency_id AND cp.case_id = cases.id AND cp.deleted_at IS NULL
            ) as total_debt")
            ->value('total_debt') ?? 0;

        // Просрочки
        $overdueCount = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->whereNull('deleted_at')
            ->where('payment_blocked', true)
            ->count();

        return ApiResponse::success([
            'revenue'           => [
                'total_received'    => (int) $payments->total_received,
                'cash'              => (int) $payments->cash_total,
                'terminal'          => (int) $payments->terminal_total,
                'bank_transfer'     => (int) $payments->bank_transfer_total,
                'online'            => (int) $payments->online_total,
                'total_payments'    => (int) $payments->total_payments,
            ],
            'cases'             => [
                'total'             => (int) $cases->total_cases,
                'contract_value'    => (int) $cases->total_contract_value,
                'fully_paid'        => (int) $cases->fully_paid,
                'partial_paid'      => (int) $cases->partial_paid,
                'unpaid'            => (int) $cases->unpaid,
                'cancelled'         => (int) $cases->cancelled,
                'blocked'           => (int) $cases->blocked,
            ],
            'refunds'           => [
                'total'             => (int) $refunds->total_refunds,
                'total_amount'      => (int) $refunds->total_refunded,
                'completed'         => (int) $refunds->completed_refunds,
                'pending'           => (int) $refunds->pending_refunds,
            ],
            'debt'              => (int) $debts,
            'overdue_count'     => $overdueCount,
            'net_revenue'       => (int) $payments->total_received - (int) ($refunds->completed_refunds ?? 0),
        ]);
    }

    /**
     * GET /finance/payments — все платежи агентства с фильтрами.
     */
    public function payments(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $query = DB::table('case_payments as cp')
            ->join('cases as c', 'c.id', '=', 'cp.case_id')
            ->leftJoin('clients as cl', 'cl.id', '=', 'c.client_id')
            ->leftJoin('users as u', 'u.id', '=', 'cp.recorded_by')
            ->where('cp.agency_id', $agencyId)
            ->whereNull('cp.deleted_at')
            ->select(
                'cp.id', 'cp.amount', 'cp.currency', 'cp.payment_method',
                'cp.paid_at', 'cp.comment', 'cp.created_at',
                'c.case_number', 'c.country_code', 'c.visa_type',
                'cl.name as client_name',
                'u.name as recorded_by_name'
            );

        // Фильтры
        if ($request->filled('method')) $query->where('cp.payment_method', $request->input('method'));
        if ($request->filled('from'))   $query->where('cp.paid_at', '>=', $request->input('from'));
        if ($request->filled('to'))     $query->where('cp.paid_at', '<=', $request->input('to') . ' 23:59:59');
        if ($request->filled('client')) $query->where('cl.name', 'ilike', '%' . $request->input('client') . '%');

        $payments = $query->orderByDesc('cp.paid_at')->paginate(50);

        return ApiResponse::success($payments);
    }

    /**
     * GET /finance/debts — заявки с задолженностью.
     */
    public function debts(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $debts = DB::table('cases as c')
            ->leftJoin('clients as cl', 'cl.id', '=', 'c.client_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.assigned_to')
            ->where('c.agency_id', $agencyId)
            ->whereNull('c.deleted_at')
            ->where('c.total_price', '>', 0)
            ->whereIn('c.payment_status', ['unpaid', 'prepayment'])
            ->whereNotIn('c.public_status', ['cancelled', 'draft'])
            ->select(
                'c.id', 'c.case_number', 'c.country_code', 'c.visa_type',
                'c.total_price', 'c.payment_status', 'c.payment_deadline', 'c.payment_blocked',
                'c.stage', 'c.created_at',
                'cl.name as client_name', 'cl.phone as client_phone',
                'u.name as manager_name'
            )
            ->selectRaw("(SELECT COALESCE(SUM(cp.amount), 0) FROM case_payments cp WHERE cp.case_id = c.id AND cp.deleted_at IS NULL) as paid_amount")
            ->orderBy('c.payment_deadline')
            ->get()
            ->map(function ($d) {
                $d->remaining = $d->total_price - $d->paid_amount;
                $d->is_overdue = $d->payment_deadline && $d->payment_deadline < now()->toDateString();
                return $d;
            });

        return ApiResponse::success(['debts' => $debts]);
    }

    /**
     * GET /finance/by-manager — выручка по менеджерам.
     */
    public function byManager(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $data = DB::table('case_payments as cp')
            ->join('cases as c', 'c.id', '=', 'cp.case_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.assigned_to')
            ->where('cp.agency_id', $agencyId)
            ->whereNull('cp.deleted_at')
            ->groupBy('c.assigned_to', 'u.name')
            ->select(
                'c.assigned_to',
                'u.name as manager_name',
                DB::raw('COUNT(DISTINCT cp.case_id) as cases_count'),
                DB::raw('COUNT(cp.id) as payments_count'),
                DB::raw('COALESCE(SUM(cp.amount), 0) as total_amount')
            )
            ->orderByDesc('total_amount')
            ->get();

        return ApiResponse::success(['managers' => $data]);
    }

    /**
     * GET /finance/by-country — выручка по странам.
     */
    public function byCountry(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $data = DB::table('case_payments as cp')
            ->join('cases as c', 'c.id', '=', 'cp.case_id')
            ->where('cp.agency_id', $agencyId)
            ->whereNull('cp.deleted_at')
            ->groupBy('c.country_code')
            ->select(
                'c.country_code',
                DB::raw('COUNT(DISTINCT cp.case_id) as cases_count'),
                DB::raw('COALESCE(SUM(cp.amount), 0) as total_amount')
            )
            ->orderByDesc('total_amount')
            ->get();

        return ApiResponse::success(['countries' => $data]);
    }
}
