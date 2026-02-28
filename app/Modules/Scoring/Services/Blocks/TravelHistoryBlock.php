<?php

namespace App\Modules\Scoring\Services\Blocks;

use App\Modules\Scoring\Models\ClientProfile;

class TravelHistoryBlock
{
    public function calculate(ClientProfile $p): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];

        // Позитивная история виз
        if ($p->has_us_visa) {
            $raw += 25;
        }
        if ($p->has_schengen_visa) {
            $raw += 20;
        }
        if ($p->has_uk_visa) {
            $raw += 20;
        }

        // Чистая история подач
        if ($p->previous_refusals === 0) {
            $raw += 35;
        } elseif ($p->previous_refusals === 1) {
            $raw -= 20;
            $flags[] = 'Один отказ в визе в истории';
            $recs[]  = 'Подготовьте дополнительные документы — в анкете был отказ';
        } else {
            $raw -= 40;
            $flags[] = "Множественные отказы ({$p->previous_refusals}) — серьёзный риск";
        }

        // Overstay — красный флаг
        if ($p->has_overstay) {
            $raw -= 40;
            $flags[] = 'Нарушение сроков пребывания (overstay) — критический флаг';
        }

        if ($raw === 0 && ! $p->has_us_visa && ! $p->has_schengen_visa && ! $p->has_uk_visa) {
            $recs[] = 'Нет истории виз. Начните с более лояльных направлений (ОАЭ, Турция)';
        }

        return [
            'score'           => round(max(min($raw, 100), 0), 2),
            'flags'           => $flags,
            'recommendations' => $recs,
        ];
    }
}
