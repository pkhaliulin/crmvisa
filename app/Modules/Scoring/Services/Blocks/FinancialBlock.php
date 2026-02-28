<?php

namespace App\Modules\Scoring\Services\Blocks;

use App\Modules\Scoring\Models\ClientProfile;

class FinancialBlock
{
    // Максимум сырых баллов = 85
    private const MAX_RAW = 85;

    public function calculate(ClientProfile $p): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];

        // Доход
        $raw += match (true) {
            $p->monthly_income > 5000  => 50,
            $p->monthly_income >= 2000 => 35,
            $p->monthly_income >= 1000 => 20,
            $p->monthly_income >= 500  => 10,
            default                    => 0,
        };

        if ($p->monthly_income < 500) {
            $flags[] = 'Доход ниже минимального порога';
            $recs[]  = 'Подтвердите официальный доход от $500/мес';
        }

        // Официальный доход
        if ($p->income_type === 'official') {
            $raw += 10;
        } else {
            $recs[] = 'Официальное подтверждение дохода повысит оценку на +10';
        }

        // Выписка банка
        if ($p->bank_history_months >= 6) {
            $raw += 10;
        } else {
            $recs[] = 'Предоставьте выписку за 6+ месяцев (+10 баллов)';
        }

        // Стабильность баланса
        if ($p->bank_balance_stable) {
            $raw += 5;
        }

        // Депозиты
        if ($p->has_fixed_deposit) {
            $raw += 5;
        } else {
            $recs[] = 'Наличие срочного депозита усиливает финансовый профиль (+5)';
        }

        // Инвестиции
        if ($p->has_investments) {
            $raw += 5;
        }

        return [
            'score'           => round(min($raw / self::MAX_RAW * 100, 100), 2),
            'flags'           => $flags,
            'recommendations' => $recs,
        ];
    }
}
