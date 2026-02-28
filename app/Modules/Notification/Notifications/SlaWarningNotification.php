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

    public function __construct(public VisaCase $case) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLeft  = Carbon::now()->diffInDays($this->case->critical_date, false);
        $clientName = $this->case->client->name;
        $country   = $this->case->country_code;
        $stage     = config("stages.{$this->case->stage}.label", $this->case->stage);

        return (new MailMessage)
            ->subject("SLA Warning: {$clientName} — {$country} visa — {$daysLeft} days left")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Case for **{$clientName}** ({$country}, {$this->case->visa_type}) is approaching its deadline.")
            ->line("Current stage: **{$stage}**")
            ->line("Days remaining: **{$daysLeft}**")
            ->line("Deadline: **{$this->case->critical_date->toDateString()}**")
            ->action('View Case', url("/api/v1/cases/{$this->case->id}"))
            ->salutation('VisaBor CRM');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'sla_warning',
            'case_id'       => $this->case->id,
            'client_name'   => $this->case->client->name,
            'country_code'  => $this->case->country_code,
            'critical_date' => $this->case->critical_date->toDateString(),
            'days_left'     => Carbon::now()->diffInDays($this->case->critical_date, false),
        ];
    }
}
