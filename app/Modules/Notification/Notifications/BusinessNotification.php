<?php

namespace App\Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Универсальное уведомление для всех бизнес-событий платформы.
 * Единый класс вместо отдельного Notification на каждый тип события.
 */
class BusinessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    private array $resolvedChannels = ['database'];

    public function __construct(
        private readonly string $eventType,
        private readonly array $data,
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
        return array_merge(['type' => $this->eventType], $this->data);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $subject = $this->data['subject'] ?? $this->resolveSubject();
        $message = $this->data['message'] ?? $this->resolveMessage();

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line($message);

        if (!empty($this->data['details'])) {
            foreach ((array) $this->data['details'] as $detail) {
                $mail->line($detail);
            }
        }

        return $mail->salutation('VisaCRM');
    }

    public function toTelegram(mixed $notifiable): array
    {
        $lines = [
            '<b>' . ($this->data['brand'] ?? 'VisaCRM') . '</b>',
            '',
            $this->data['message'] ?? $this->resolveMessage(),
        ];

        if (!empty($this->data['details'])) {
            $lines[] = '';
            foreach ((array) $this->data['details'] as $detail) {
                $lines[] = $detail;
            }
        }

        return [
            'text'       => implode("\n", $lines),
            'parse_mode' => 'HTML',
        ];
    }

    public function toSms(mixed $notifiable): array
    {
        return [
            'text' => $this->data['sms'] ?? mb_substr($this->resolveMessage(), 0, 160),
        ];
    }

    private function resolveSubject(): string
    {
        $subjects = [
            'case.created'         => 'Новая заявка создана',
            'case.assigned'        => 'Менеджер назначен',
            'sla.violation'        => 'Нарушение SLA',
            'sla.warning'          => 'Предупреждение SLA',
            'document.uploaded'    => 'Документ загружен',
            'payment.received'     => 'Платёж получен',
            'subscription.changed' => 'Подписка изменена',
            'subscription.expired' => 'Подписка истекла',
            'client.registered'    => 'Новый клиент',
        ];

        return $subjects[$this->eventType] ?? 'Уведомление VisaCRM';
    }

    private function resolveMessage(): string
    {
        return $this->data['message'] ?? $this->resolveSubject();
    }
}
