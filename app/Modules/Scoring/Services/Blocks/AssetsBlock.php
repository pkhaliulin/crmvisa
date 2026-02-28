<?php

namespace App\Modules\Scoring\Services\Blocks;

use App\Modules\Scoring\Models\ClientProfile;

class AssetsBlock
{
    private const MAX_RAW = 100;

    public function calculate(ClientProfile $p): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];

        if ($p->has_real_estate) {
            $raw += 40;
        } else {
            $recs[] = 'Наличие недвижимости существенно повышает оценку (+40)';
        }

        if ($p->has_car) {
            $raw += 20;
        }

        if ($p->has_business) {
            $raw += 40;
        }

        if ($raw === 0) {
            $flags[] = 'Нет подтверждённых активов в стране проживания';
            $recs[]  = 'Укажите имущество — недвижимость или автомобиль';
        }

        return [
            'score'           => round(min($raw / self::MAX_RAW * 100, 100), 2),
            'flags'           => $flags,
            'recommendations' => $recs,
        ];
    }
}
