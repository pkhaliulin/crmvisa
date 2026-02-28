<?php

namespace App\Modules\Scoring\Services\Blocks;

use App\Modules\Scoring\Models\ClientProfile;

class PersonalBlock
{
    private const MAX_RAW = 60;

    public function calculate(ClientProfile $p): array
    {
        $flags = [];
        $recs  = [];

        // Судимость — блокировка
        if ($p->has_criminal_record) {
            return [
                'score'           => 0,
                'is_blocked'      => true,
                'flags'           => ['Судимость — подача визы крайне маловероятна'],
                'recommendations' => ['Необходима юридическая консультация перед подачей'],
            ];
        }

        $raw = 0;

        // Образование
        $raw += match ($p->education_level) {
            'phd'       => 30,
            'master'    => 25,
            'bachelor'  => 20,
            'secondary' => 10,
            'none'      => 0,
            default     => 10,
        };

        // Возраст (наилучший: 25–45)
        $raw += match (true) {
            $p->age >= 25 && $p->age <= 45 => 30,
            $p->age >= 18 && $p->age < 25  => 20,
            $p->age > 45 && $p->age <= 60  => 20,
            $p->age > 60                   => 10,
            default                        => 15,
        };

        return [
            'score'           => round(min($raw / self::MAX_RAW * 100, 100), 2),
            'is_blocked'      => false,
            'flags'           => $flags,
            'recommendations' => $recs,
        ];
    }
}
