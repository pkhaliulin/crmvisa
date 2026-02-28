<?php

namespace App\Modules\Scoring\Services\Blocks;

use App\Modules\Scoring\Models\ClientProfile;

class FamilyBlock
{
    private const MAX_RAW = 90;

    public function calculate(ClientProfile $p): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];

        // Семейное положение
        $raw += match ($p->marital_status) {
            'married' => 30,
            'widowed' => 15,
            'divorced'=> 10,
            'single'  => 10,
            default   => 10,
        };

        // Супруг официально работает
        if ($p->marital_status === 'married' && $p->spouse_employed) {
            $raw += 10;
        }

        // Дети остаются дома — сильнейшая привязанность к родине
        if ($p->children_staying_home && $p->children_count > 0) {
            $childBonus = min($p->children_count * 20, 40);
            $raw += $childBonus;
        } elseif ($p->children_count > 0 && ! $p->children_staying_home) {
            $flags[] = 'Дети не остаются на родине — риск невозврата';
        }

        // Иждивенцы (пожилые родители) — привязанность к родине
        if ($p->dependents_count > 0) {
            $raw += 10;
        }

        return [
            'score'           => round(min($raw / self::MAX_RAW * 100, 100), 2),
            'flags'           => $flags,
            'recommendations' => $recs,
        ];
    }
}
