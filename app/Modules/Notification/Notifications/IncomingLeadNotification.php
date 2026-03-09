<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Notification\Channels\SmsChannel;
use App\Modules\Notification\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncomingLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    private array $resolvedChannels = ['database'];

    public function __construct(
        private readonly VisaCase $case,
        private readonly Client $client,
        private readonly ?string $source = null,
    ) {}

    public function setChannels(array $channels): void
    {
        $this->resolvedChannels = $channels;
    }

    public function via(mixed $notifiable): array
    {
        return $this->resolvedChannels;
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type'         => 'lead.incoming',
            'case_id'      => $this->case->id,
            'case_number'  => $this->case->case_number,
            'client_name'  => $this->client->name,
            'client_phone' => $this->client->phone,
            'country_code' => $this->case->country_code,
            'visa_type'    => $this->case->visa_type,
            'source'       => $this->source ?? 'api',
            'message'      => "Новый лид: {$this->client->name} ({$this->case->country_code})",
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новый лид -- ' . $this->client->name)
            ->greeting('Новая заявка через API!')
            ->line("Клиент: {$this->client->name}")
            ->line("Телефон: {$this->client->phone}")
            ->line("Страна: {$this->case->country_code}")
            ->line("Тип визы: {$this->case->visa_type}")
            ->line("Источник: " . ($this->source ?? 'API'))
            ->action('Открыть в CRM', url('/app/cases'))
            ->salutation('VisaCRM');
    }

    public function toTelegram(mixed $notifiable): array
    {
        $agencyName = $this->case->agency?->name ?? 'VisaCRM';

        $text = implode("\n", [
            "<b>{$agencyName}</b>",
            "",
            "Новый лид через API!",
            "<b>{$this->client->name}</b>",
            "Телефон: {$this->client->phone}",
            "Страна: {$this->case->country_code}",
            "Тип визы: {$this->case->visa_type}",
            "Источник: " . ($this->source ?? 'API'),
            "",
            "Заявка #{$this->case->case_number}",
        ]);

        return [
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];
    }

    public function toSms(mixed $notifiable): array
    {
        return [
            'text' => "Новый лид: {$this->client->name}, {$this->case->country_code}. Заявка #{$this->case->case_number}",
        ];
    }
}
