<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\CasePayment;
use App\Modules\Case\Models\VisaCase;
use Illuminate\Support\Facades\Auth;

class CasePaymentService
{
    /**
     * Записать платёж и пересчитать статус оплаты кейса.
     */
    public function recordPayment(VisaCase $case, array $data): CasePayment
    {
        $payment = CasePayment::create([
            'case_id'        => $case->id,
            'agency_id'      => $case->agency_id,
            'amount'         => $data['amount'],
            'currency'       => $data['currency'] ?? $case->price_currency ?? 'UZS',
            'payment_method' => $data['payment_method'],
            'paid_at'        => $data['paid_at'] ?? now(),
            'recorded_by'    => Auth::id(),
            'comment'        => $data['comment'] ?? null,
            'metadata'       => $data['metadata'] ?? null,
        ]);

        $this->recalculatePaymentStatus($case);

        CaseService::logActivity(
            $case,
            'payment_recorded',
            "Записана оплата: " . number_format($data['amount']) . " " . ($data['currency'] ?? 'UZS'),
            [
                'payment_id' => $payment->id,
                'amount'     => $data['amount'],
                'method'     => $data['payment_method'],
            ],
            true,
        );

        return $payment;
    }

    /**
     * Удалить платёж (soft delete) и пересчитать статус.
     */
    public function deletePayment(CasePayment $payment): void
    {
        $case = $payment->case;
        $payment->delete();

        $this->recalculatePaymentStatus($case);

        CaseService::logActivity(
            $case,
            'payment_deleted',
            "Удалена оплата: " . number_format($payment->amount) . " " . $payment->currency,
            ['payment_id' => $payment->id],
            true,
        );
    }

    /**
     * Установить стоимость услуги.
     */
    public function setTotalPrice(VisaCase $case, int $amount, string $currency = 'UZS'): void
    {
        $case->update([
            'total_price'    => $amount,
            'price_currency' => $currency,
        ]);

        $this->recalculatePaymentStatus($case);
    }

    /**
     * Установить дедлайн доплаты.
     */
    public function setPaymentDeadline(VisaCase $case, ?string $deadline): void
    {
        $case->update(['payment_deadline' => $deadline]);
    }

    /**
     * SSOT: пересчёт payment_status на основе суммы платежей.
     */
    public function recalculatePaymentStatus(VisaCase $case): void
    {
        $case->refresh();
        $paidAmount = $case->paidAmount();
        $totalPrice = $case->total_price ?? 0;

        if ($paidAmount <= 0) {
            $status = 'unpaid';
        } elseif ($totalPrice > 0 && $paidAmount >= $totalPrice) {
            $status = 'paid';
        } else {
            $status = 'prepayment';
        }

        $updates = ['payment_status' => $status];

        // Разблокировать кейс при полной оплате
        if ($status === 'paid' && $case->payment_blocked) {
            $updates['payment_blocked'] = false;
        }

        $case->update($updates);
    }

    /**
     * Сводка по оплате кейса.
     */
    public function getPaymentSummary(VisaCase $case): array
    {
        $payments = CasePayment::where('case_id', $case->id)
            ->orderByDesc('paid_at')
            ->get();

        $paidAmount = $payments->sum('amount');
        $totalPrice = $case->total_price ?? 0;

        return [
            'total_price'      => $totalPrice,
            'price_currency'   => $case->price_currency ?? 'UZS',
            'paid_amount'      => $paidAmount,
            'remaining_balance'=> max(0, $totalPrice - $paidAmount),
            'payment_status'   => $case->payment_status ?? 'unpaid',
            'payment_deadline' => $case->payment_deadline?->toDateString(),
            'payment_blocked'  => (bool) $case->payment_blocked,
            'payments'         => $payments->map(fn ($p) => [
                'id'             => $p->id,
                'amount'         => $p->amount,
                'currency'       => $p->currency,
                'payment_method' => $p->payment_method,
                'paid_at'        => $p->paid_at?->toDateTimeString(),
                'recorded_by'    => $p->recorder?->name ?? null,
                'comment'        => $p->comment,
                'created_at'     => $p->created_at?->toDateTimeString(),
            ]),
        ];
    }
}
