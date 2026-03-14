<?php

namespace App\Modules\Finance\Services;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Support\Str;

class ContractService
{
    /**
     * Сгенерировать номер договора и зафиксировать.
     */
    public function acceptContract(VisaCase $case): VisaCase
    {
        if (!$case->contract_number) {
            $case->update([
                'contract_number'      => $this->generateContractNumber($case),
                'contract_accepted_at' => now(),
            ]);
        }

        return $case->fresh();
    }

    /**
     * Данные для печатного договора.
     */
    public function getContractData(VisaCase $case): array
    {
        $case->load(['client', 'agency', 'assignee']);

        $totalPrice = $case->total_price ?? 0;
        $paidAmount = $case->paidAmount();
        $remaining = $case->remainingBalance();

        return [
            'contract_number'  => $case->contract_number ?: $this->generateContractNumber($case),
            'date'             => $case->contract_accepted_at?->toDateString() ?? now()->toDateString(),
            'agency'           => [
                'name'    => $case->agency->name ?? '',
                'address' => $case->agency->address ?? '',
                'phone'   => $case->agency->phone ?? '',
                'email'   => $case->agency->email ?? '',
            ],
            'client'           => [
                'name'  => $case->client->name ?? '',
                'phone' => $case->client->phone ?? '',
            ],
            'service'          => [
                'country'   => $case->country_code,
                'visa_type' => $case->visa_type,
                'description' => ($case->visa_type ?? '') . ' — ' . ($case->country_code ?? ''),
            ],
            'payment'          => [
                'total_price'    => $totalPrice,
                'prepayment'     => $paidAmount,
                'remaining'      => $remaining,
                'currency'       => $case->price_currency ?? 'UZS',
                'deadline'       => $case->payment_deadline?->toDateString(),
            ],
            'cancellation_policy' => $this->getCancellationPolicy(),
            'case_number'      => $case->case_number,
        ];
    }

    /**
     * Рассчитать сумму возврата при отмене.
     *
     * Политика:
     * - До начала работы (lead, qualification): 100% возврат
     * - Сбор документов (documents): 70% возврат
     * - Проверка документов (doc_review): 50% возврат
     * - Перевод (translation): 30% возврат
     * - Готово к подаче и далее (ready, review, result): 0% возврат
     */
    public function calculateRefund(VisaCase $case): array
    {
        $paidAmount = $case->paidAmount();

        $refundPercent = match ($case->stage) {
            'lead', 'qualification' => 100,
            'documents'             => 70,
            'doc_review'            => 50,
            'translation'           => 30,
            'ready'                 => 10,
            default                 => 0,
        };

        $refundAmount = (int) round($paidAmount * $refundPercent / 100);

        return [
            'paid_amount'    => $paidAmount,
            'refund_percent' => $refundPercent,
            'refund_amount'  => $refundAmount,
            'retained'       => $paidAmount - $refundAmount,
            'stage'          => $case->stage,
            'currency'       => $case->price_currency ?? 'UZS',
        ];
    }

    /**
     * Политика отмены (текст для договора).
     */
    public function getCancellationPolicy(): array
    {
        return [
            ['stage' => 'lead/qualification', 'refund' => '100%', 'description' => 'До начала работы — полный возврат'],
            ['stage' => 'documents',          'refund' => '70%',  'description' => 'Этап сбора документов — возврат 70%'],
            ['stage' => 'doc_review',         'refund' => '50%',  'description' => 'Проверка документов — возврат 50%'],
            ['stage' => 'translation',        'refund' => '30%',  'description' => 'Перевод документов — возврат 30%'],
            ['stage' => 'ready',              'refund' => '10%',  'description' => 'Готово к подаче — возврат 10%'],
            ['stage' => 'review/result',      'refund' => '0%',   'description' => 'Подано / Результат — возврат невозможен'],
        ];
    }

    private function generateContractNumber(VisaCase $case): string
    {
        $date = now()->format('ymd');
        $short = strtoupper(substr(md5($case->id), 0, 4));
        return "VB-{$date}-{$short}";
    }
}
