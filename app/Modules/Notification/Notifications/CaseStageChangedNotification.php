<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление клиенту о смене этапа заявки.
 * Бренд определяется через NotificationService.dispatchToClient().
 */
class CaseStageChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    private array $resolvedChannels = ['mail', 'database'];

    private array $brand = [
        'name'               => 'VisaBor',
        'email_from'         => 'noreply@visabor.uz',
        'email_name'         => 'VisaBor',
        'sms_sender'         => 'VisaBor',
        'telegram_signature' => 'VisaBor',
    ];

    public function __construct(
        public VisaCase $case,
        public string $previousStage,
    ) {}

    public function setChannels(array $channels): void
    {
        $this->resolvedChannels = $channels;
    }

    public function setBrand(array $brand): void
    {
        $this->brand = array_merge($this->brand, $brand);
    }

    public function via(object $notifiable): array
    {
        return $this->resolvedChannels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stageLabel    = config("stages.{$this->case->stage}.label", $this->case->stage);
        $clientMessage = config("stages.{$this->case->stage}.client_msg", $stageLabel);
        $clientName    = $this->case->client->name;

        return (new MailMessage)
            ->from($this->brand['email_from'], $this->brand['email_name'])
            ->subject("Обновление заявки: {$clientName} — {$this->case->country_code}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Заявка перешла на новый этап.")
            ->line("**Статус: {$clientMessage}**")
            ->line("Страна: {$this->case->country_code} | Тип визы: {$this->case->visa_type}")
            ->salutation($this->brand['name']);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'case.status_changed',
            'case_id'        => $this->case->id,
            'previous_stage' => $this->previousStage,
            'new_stage'      => $this->case->stage,
            'client_message' => config("stages.{$this->case->stage}.client_msg"),
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        $stageLabel = config("stages.{$this->case->stage}.label", $this->case->stage);
        $clientMsg  = config("stages.{$this->case->stage}.client_msg", $stageLabel);

        return [
            'text'       => implode("\n", [
                "<b>{$this->brand['telegram_signature']}</b>",
                '',
                "Обновление по вашей заявке:",
                "<b>{$clientMsg}</b>",
                '',
                "Страна: {$this->case->country_code}",
                "Тип визы: {$this->case->visa_type}",
            ]),
            'parse_mode' => 'HTML',
        ];
    }

    public function toSms(mixed $notifiable): array
    {
        $stageLabel = config("stages.{$this->case->stage}.label", $this->case->stage);
        return [
            'text'   => "Заявка #{$this->case->case_number}: этап «{$stageLabel}»",
            'sender' => $this->brand['sms_sender'],
        ];
    }
}
