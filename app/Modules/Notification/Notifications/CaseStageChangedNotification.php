<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseStageChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VisaCase $case,
        public string $previousStage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stageLabel    = config("stages.{$this->case->stage}.label", $this->case->stage);
        $clientMessage = config("stages.{$this->case->stage}.client_msg", $stageLabel);
        $clientName    = $this->case->client->name;

        return (new MailMessage)
            ->subject("Case update: {$clientName} — {$this->case->country_code} visa")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Your visa case has moved to a new stage.")
            ->line("**Status: {$clientMessage}**")
            ->line("Country: {$this->case->country_code} | Type: {$this->case->visa_type}")
            ->salutation('VisaBor');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'stage_changed',
            'case_id'        => $this->case->id,
            'previous_stage' => $this->previousStage,
            'new_stage'      => $this->case->stage,
            'client_message' => config("stages.{$this->case->stage}.client_msg"),
        ];
    }
}
