<?php

namespace App\Modules\Scoring\Services\Blocks;

use App\Modules\Scoring\Models\ClientProfile;

class EmploymentBlock
{
    private const MAX_RAW = 85;

    public function calculate(ClientProfile $p): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];

        // Тип занятости
        $raw += match ($p->employment_type) {
            'government'    => 50,
            'business_owner'=> 40,
            'private'       => 30,
            'retired'       => 25,
            'self_employed' => 20,
            'student'       => 10,
            'unemployed'    => 0,
            default         => 0,
        };

        if ($p->employment_type === 'unemployed') {
            $flags[] = 'Отсутствие занятости — критический фактор';
        }

        // Уровень должности
        $raw += match ($p->position_level) {
            'executive' => 15,
            'senior'    => 10,
            default     => 0,
        };

        // Стаж на текущем месте
        if ($p->years_at_current_job >= 3) {
            $raw += 10;
        } elseif ($p->years_at_current_job >= 1) {
            $raw += 5;
        } else {
            $recs[] = 'Стаж от 1 года на текущем месте повысит оценку (+5)';
        }

        // Отсутствие пробелов в занятости
        if (! $p->has_employment_gaps) {
            $raw += 10;
        } else {
            $flags[] = 'Пробелы в трудовой истории';
            $recs[]  = 'Подготовьте объяснение пробелов в занятости';
        }

        return [
            'score'           => round(min($raw / self::MAX_RAW * 100, 100), 2),
            'flags'           => $flags,
            'recommendations' => $recs,
        ];
    }
}
