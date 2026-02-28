<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Notification\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TelegramCaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly VisaCase $case,
        private readonly string $previousStage,
    ) {}

    public function via(mixed $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(mixed $notifiable): array
    {
        $agency     = $this->case->agency;
        $stage      = config("stages.{$this->case->stage}");
        $clientMsg  = $stage['label'] ?? $this->case->stage;
        $stageMsg   = $stage['client_msg'] ?? 'Статус вашей заявки обновлён.';

        // Брендинг: свой клиент → имя агентства, маркетплейс → CRMBOR
        $isMarketplace = $notifiable->source === 'marketplace';
        $brandName     = $isMarketplace
            ? 'CRMBOR'
            : ($agency?->name ?? 'Ваше агентство');

        $text = implode("\n", [
            "🏢 <b>{$brandName}</b>",
            "",
            "Обновление по вашей заявке:",
            "<b>{$clientMsg}</b>",
            "",
            $stageMsg,
            "",
            "🇺🇿 Страна: {$this->case->country_code}",
            "📋 Тип визы: {$this->case->visa_type}",
        ]);

        if ($this->case->critical_date) {
            $text .= "\n⏰ Дедлайн: {$this->case->critical_date->format('d.m.Y')}";
        }

        return [
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];
    }
}
