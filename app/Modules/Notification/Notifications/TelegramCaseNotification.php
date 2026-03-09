<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Notification\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Telegram-уведомление клиенту о смене статуса заявки.
 * Бренд устанавливается через setBrand().
 */
class TelegramCaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    private array $resolvedChannels;

    private array $brand = [
        'name'               => 'VisaBor',
        'telegram_signature' => 'VisaBor',
    ];

    public function __construct(
        private readonly VisaCase $case,
        private readonly string $previousStage,
    ) {
        $this->resolvedChannels = [TelegramChannel::class];
    }

    public function setChannels(array $channels): void
    {
        $this->resolvedChannels = $channels;
    }

    public function setBrand(array $brand): void
    {
        $this->brand = array_merge($this->brand, $brand);
    }

    public function via(mixed $notifiable): array
    {
        return $this->resolvedChannels;
    }

    public function toTelegram(mixed $notifiable): array
    {
        $stage      = config("stages.{$this->case->stage}");
        $clientMsg  = $stage['label'] ?? $this->case->stage;
        $stageMsg   = $stage['client_msg'] ?? 'Статус вашей заявки обновлён.';

        $text = implode("\n", [
            "<b>{$this->brand['telegram_signature']}</b>",
            "",
            "Обновление по вашей заявке:",
            "<b>{$clientMsg}</b>",
            "",
            $stageMsg,
            "",
            "Страна: {$this->case->country_code}",
            "Тип визы: {$this->case->visa_type}",
        ]);

        if ($this->case->critical_date) {
            $text .= "\nДедлайн: {$this->case->critical_date->format('d.m.Y')}";
        }

        return [
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];
    }
}
