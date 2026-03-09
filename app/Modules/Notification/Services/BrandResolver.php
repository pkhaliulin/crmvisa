<?php

namespace App\Modules\Notification\Services;

use App\Modules\Agency\Models\Agency;

/**
 * Определяет бренд для уведомлений в зависимости от аудитории.
 *
 * Аудитории:
 *  - client    → VisaBor (маркетплейс) или имя агентства (direct)
 *  - agency    → VisaCRM (B2B SaaS)
 *  - superadmin → VisaBor Platform
 *
 * Источник клиента:
 *  - marketplace → бренд VisaBor
 *  - direct/referral/other → бренд агентства
 */
class BrandResolver
{
    /**
     * Получить брендинг для уведомления.
     *
     * @param string      $audience 'client' | 'agency' | 'superadmin'
     * @param Agency|null $agency
     * @param string|null $clientSource 'marketplace' | 'direct' | 'referral' | 'other'
     * @return array{name: string, email_from: string, email_name: string, sms_sender: string, telegram_signature: string}
     */
    public static function resolve(
        string $audience,
        ?Agency $agency = null,
        ?string $clientSource = null,
    ): array {
        return match ($audience) {
            'client'     => self::resolveClient($agency, $clientSource),
            'agency'     => self::resolveAgency($agency),
            'superadmin' => self::resolveSuperadmin(),
            default      => self::resolveAgency($agency),
        };
    }

    /**
     * Клиентские уведомления.
     * Маркетплейс клиенты видят VisaBor, прямые — имя агентства.
     */
    private static function resolveClient(?Agency $agency, ?string $clientSource): array
    {
        $isMarketplace = $clientSource === 'marketplace';

        if ($isMarketplace) {
            return [
                'name'               => 'VisaBor',
                'email_from'         => config('notification.brands.visabor.email', 'noreply@visabor.uz'),
                'email_name'         => 'VisaBor',
                'sms_sender'         => config('notification.brands.visabor.sms_sender', 'VisaBor'),
                'telegram_signature' => 'VisaBor',
            ];
        }

        // Прямой клиент агентства
        $agencyName = $agency?->name ?? 'VisaBor';

        return [
            'name'               => $agencyName,
            'email_from'         => $agency?->notification_email ?? config('notification.brands.visabor.email', 'noreply@visabor.uz'),
            'email_name'         => $agencyName,
            'sms_sender'         => config('notification.brands.visabor.sms_sender', 'VisaBor'),
            'telegram_signature' => $agencyName,
        ];
    }

    /**
     * Уведомления для агентств (менеджеры, владельцы).
     * Всегда от бренда VisaCRM.
     */
    private static function resolveAgency(?Agency $agency): array
    {
        return [
            'name'               => 'VisaCRM',
            'email_from'         => config('notification.brands.visacrm.email', 'noreply@visacrm.uz'),
            'email_name'         => 'VisaCRM',
            'sms_sender'         => config('notification.brands.visacrm.sms_sender', 'VisaCRM'),
            'telegram_signature' => 'VisaCRM',
        ];
    }

    /**
     * Уведомления суперадмину.
     */
    private static function resolveSuperadmin(): array
    {
        return [
            'name'               => 'VisaBor Platform',
            'email_from'         => config('notification.brands.visabor.email', 'noreply@visabor.uz'),
            'email_name'         => 'VisaBor Platform',
            'sms_sender'         => config('notification.brands.visabor.sms_sender', 'VisaBor'),
            'telegram_signature' => 'VisaBor Platform',
        ];
    }
}
