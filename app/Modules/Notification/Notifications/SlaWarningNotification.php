<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class SlaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    private array $resolvedChannels = ['mail', 'database'];

    private array $brand = [
        'name'               => 'VisaCRM',
        'email_from'         => 'noreply@visacrm.uz',
        'email_name'         => 'VisaCRM',
        'sms_sender'         => 'VisaCRM',
        'telegram_signature' => 'VisaCRM',
    ];

    public function __construct(public VisaCase $case) {}

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
        $daysLeft   = Carbon::now()->diffInDays($this->case->critical_date, false);
        $clientName = $this->case->client->name;
        $country    = $this->case->country_code;
        $stage      = config("stages.{$this->case->stage}.label", $this->case->stage);

        return (new MailMessage)
            ->from($this->brand['email_from'], $this->brand['email_name'])
            ->subject("SLA: {$clientName} — {$country}, осталось {$daysLeft} дн.")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Заявка **{$clientName}** ({$country}, {$this->case->visa_type}) приближается к дедлайну.")
            ->line("Текущий этап: **{$stage}**")
            ->line("Осталось дней: **{$daysLeft}**")
            ->line("Дедлайн: **{$this->case->critical_date->toDateString()}**")
            ->salutation($this->brand['name']);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'sla.warning',
            'case_id'       => $this->case->id,
            'case_number'   => $this->case->case_number,
            'client_name'   => $this->case->client->name,
            'country_code'  => $this->case->country_code,
            'critical_date' => $this->case->critical_date->toDateString(),
            'days_left'     => Carbon::now()->diffInDays($this->case->critical_date, false),
        ];
    }

    public function toTelegram(mixed $notifiable): array
    {
        $daysLeft   = Carbon::now()->diffInDays($this->case->critical_date, false);
        $clientName = $this->case->client?->name ?? 'Клиент';

        return [
            'text'       => implode("\n", [
                "<b>{$this->brand['telegram_signature']}</b>",
                '',
                "SLA: заявка #{$this->case->case_number}",
                "Клиент: {$clientName}",
                "Осталось: <b>{$daysLeft} дн.</b>",
                "Дедлайн: {$this->case->critical_date->format('d.m.Y')}",
            ]),
            'parse_mode' => 'HTML',
        ];
    }

    public function toSms(mixed $notifiable): array
    {
        $daysLeft = Carbon::now()->diffInDays($this->case->critical_date, false);
        return [
            'text'   => "SLA! Заявка #{$this->case->case_number}: осталось {$daysLeft} дн.",
            'sender' => $this->brand['sms_sender'],
        ];
    }
}
