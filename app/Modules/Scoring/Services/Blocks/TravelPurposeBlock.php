<?php

namespace App\Modules\Scoring\Services\Blocks;

use App\Modules\Scoring\Models\ClientProfile;

class TravelPurposeBlock
{
    private const MAX_RAW = 45;

    public function calculate(ClientProfile $p): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];

        if ($p->has_return_ticket) {
            $raw += 15;
        } else {
            $recs[] = 'Добавьте обратный билет — увеличит оценку (+15)';
        }

        if ($p->has_hotel_booking) {
            $raw += 10;
        } else {
            $recs[] = 'Бронирование отеля повышает доверие консула (+10)';
        }

        // Приглашение
        if ($p->has_invitation_letter) {
            // Бизнес + приглашение = бонус
            if ($p->travel_purpose === 'business') {
                $raw += 15;
            } else {
                $raw += 10;
            }
        } else {
            $recs[] = 'Письмо-приглашение значительно усилит заявку (+10–15)';
        }

        // Продолжительность поездки
        if ($p->trip_duration_days > 0 && $p->trip_duration_days <= 14) {
            $raw += 5; // Короткая поездка — ниже риск невозврата
        } elseif ($p->trip_duration_days > 60) {
            $raw -= 5;
            $flags[] = 'Длительная поездка (>60 дней) повышает риск невозврата';
        }

        return [
            'score'           => round(max(min($raw / self::MAX_RAW * 100, 100), 0), 2),
            'flags'           => $flags,
            'recommendations' => $recs,
        ];
    }
}
