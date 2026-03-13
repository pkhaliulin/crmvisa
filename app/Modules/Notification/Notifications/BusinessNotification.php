<?php

namespace App\Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Универсальное уведомление для всех бизнес-событий платформы.
 * Единый класс вместо отдельного Notification на каждый тип события.
 * Бренд устанавливается через setBrand() из NotificationService.
 */
class BusinessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    private array $resolvedChannels = ['database'];

    private array $brand = [
        'name'               => 'VisaCRM',
        'email_from'         => 'noreply@visacrm.uz',
        'email_name'         => 'VisaCRM',
        'sms_sender'         => 'VisaCRM',
        'telegram_signature' => 'VisaCRM',
    ];

    public function __construct(
        private readonly string $eventType,
        private readonly array $data,
    ) {}

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

    public function toDatabase(mixed $notifiable): array
    {
        return array_merge(['type' => $this->eventType], $this->data);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $subject = $this->data['subject'] ?? $this->resolveSubject();
        $message = $this->data['message'] ?? $this->resolveMessage();

        $mail = (new MailMessage)
            ->from($this->brand['email_from'], $this->brand['email_name'])
            ->subject($subject)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line($message);

        if (!empty($this->data['details'])) {
            foreach ((array) $this->data['details'] as $detail) {
                $mail->line($detail);
            }
        }

        return $mail->salutation($this->brand['name']);
    }

    public function toTelegram(mixed $notifiable): array
    {
        $lines = [
            '<b>' . $this->brand['telegram_signature'] . '</b>',
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
        $text = $this->data['sms'] ?? mb_substr($this->resolveMessage(), 0, 160);

        return [
            'text'   => $text,
            'sender' => $this->brand['sms_sender'],
        ];
    }

    private function resolveSubject(): string
    {
        $subjects = [
            'case.created'         => 'Новая заявка создана',
            'case.status_changed'  => 'Статус заявки изменён',
            'case.assigned'        => 'Менеджер назначен',
            'case.completed'       => 'Заявка завершена — виза одобрена',
            'case.rejected'        => 'Заявка завершена — отказ',
            'case.cancelled'       => 'Заявка отменена',
            'sla.violation'        => 'Нарушение SLA',
            'sla.warning'          => 'Предупреждение SLA',
            'document.uploaded'    => 'Документ загружен',
            'document.reviewed'    => 'Документ проверен',
            'document.rejected'    => 'Документ отклонён',
            'payment.received'     => 'Платёж получен',
            'subscription.changed' => 'Подписка изменена',
            'subscription.expired' => 'Подписка истекла',
            'client.registered'    => 'Новый клиент',
            'client.portal_created' => 'Клиент через портал',
            'scoring.completed'    => 'Скоринг рассчитан',
        ];

        return $subjects[$this->eventType] ?? "Уведомление {$this->brand['name']}";
    }

    private function resolveMessage(): string
    {
        return $this->data['message'] ?? $this->resolveSubject();
    }
}
