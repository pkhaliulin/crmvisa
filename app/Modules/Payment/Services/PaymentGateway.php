<?php

namespace App\Modules\Payment\Services;

interface PaymentGateway
{
    /**
     * Создать платёж и получить данные для редиректа.
     *
     * @param float  $amount   Сумма в сумах (UZS)
     * @param string $orderId  Внутренний ID платежа (UUID)
     * @param array  $params   Доп. параметры (description, return_url и т.д.)
     * @return array{checkout_url: string, external_id: string|null, raw: array}
     */
    public function createPayment(float $amount, string $orderId, array $params = []): array;

    /**
     * Проверить статус платежа по внешнему ID.
     *
     * @return array{status: string, external_id: string, amount: float, raw: array}
     */
    public function checkStatus(string $transactionId): array;

    /**
     * Обработать callback/webhook от платёжной системы.
     *
     * @return array{success: bool, external_id: string|null, order_id: string|null, amount: float|null, error: string|null}
     */
    public function handleCallback(array $data): array;
}
