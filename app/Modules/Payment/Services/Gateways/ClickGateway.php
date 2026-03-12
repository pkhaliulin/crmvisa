<?php

namespace App\Modules\Payment\Services\Gateways;

use App\Modules\Payment\Services\PaymentGateway;
use Illuminate\Support\Facades\Log;

class ClickGateway implements PaymentGateway
{
    private string $serviceId;
    private string $merchantId;
    private string $merchantUserId;
    private string $secretKey;

    public function __construct()
    {
        $this->serviceId      = config('services.click.service_id', '');
        $this->merchantId     = config('services.click.merchant_id', '');
        $this->merchantUserId = config('services.click.merchant_user_id', '');
        $this->secretKey      = config('services.click.secret_key', '');
    }

    /**
     * {@inheritDoc}
     */
    public function createPayment(float $amount, string $orderId, array $params = []): array
    {
        // Click.uz — формируем URL для редиректа на страницу оплаты
        $checkoutUrl = 'https://my.click.uz/services/pay?' . http_build_query([
            'service_id'       => $this->serviceId,
            'merchant_id'      => $this->merchantId,
            'merchant_user_id' => $this->merchantUserId,
            'amount'           => $amount,
            'transaction_param' => $orderId,
            'return_url'       => $params['return_url'] ?? config('app.url') . '/billing?payment=success',
        ]);

        Log::channel('billing')->info('Click: payment created', [
            'order_id' => $orderId,
            'amount'   => $amount,
        ]);

        return [
            'checkout_url' => $checkoutUrl,
            'external_id'  => null, // Click присылает click_trans_id в callback
            'raw'          => ['service_id' => $this->serviceId, 'amount' => $amount],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function checkStatus(string $transactionId): array
    {
        // Click не предоставляет pull-based API для проверки статуса;
        // статус приходит через callback (prepare/complete).
        return [
            'status'      => 'unknown',
            'external_id' => $transactionId,
            'amount'      => 0,
            'raw'         => [],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Click Merchant API:
     * action=0 — prepare (резервирование)
     * action=1 — complete (подтверждение)
     */
    public function handleCallback(array $data): array
    {
        $action          = (int) ($data['action'] ?? -1);
        $clickTransId    = $data['click_trans_id'] ?? '';
        $merchantTransId = $data['merchant_trans_id'] ?? '';
        $amount          = (float) ($data['amount'] ?? 0);
        $signString      = $data['sign_string'] ?? '';
        $signTime        = $data['sign_time'] ?? '';
        $error           = (int) ($data['error'] ?? 0);

        // Проверка подписи
        $expectedSign = md5(
            $clickTransId .
            $this->serviceId .
            $this->secretKey .
            $merchantTransId .
            $amount .
            $action .
            $signTime
        );

        if (! empty($this->secretKey) && ! hash_equals($expectedSign, $signString)) {
            Log::channel('billing')->warning('Click: invalid signature', [
                'click_trans_id'    => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
            ]);
            return [
                'success'     => false,
                'external_id' => $clickTransId,
                'order_id'    => $merchantTransId,
                'amount'      => $amount,
                'error'       => 'Invalid signature',
            ];
        }

        if ($error !== 0) {
            Log::channel('billing')->warning('Click: payment error', [
                'error'             => $error,
                'click_trans_id'    => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
            ]);
            return [
                'success'     => false,
                'external_id' => $clickTransId,
                'order_id'    => $merchantTransId,
                'amount'      => $amount,
                'error'       => "Click error code: {$error}",
            ];
        }

        if ($action === 0) {
            // Prepare — подтверждаем готовность
            Log::channel('billing')->info('Click: prepare OK', [
                'click_trans_id'    => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'amount'            => $amount,
            ]);
            return [
                'success'     => true,
                'external_id' => $clickTransId,
                'order_id'    => $merchantTransId,
                'amount'      => $amount,
                'error'       => null,
                'action'      => 'prepare',
            ];
        }

        if ($action === 1) {
            // Complete — платёж завершён
            Log::channel('billing')->info('Click: complete OK', [
                'click_trans_id'    => $clickTransId,
                'merchant_trans_id' => $merchantTransId,
                'amount'            => $amount,
            ]);
            return [
                'success'     => true,
                'external_id' => $clickTransId,
                'order_id'    => $merchantTransId,
                'amount'      => $amount,
                'error'       => null,
                'action'      => 'complete',
            ];
        }

        return [
            'success'     => false,
            'external_id' => $clickTransId,
            'order_id'    => $merchantTransId,
            'amount'      => $amount,
            'error'       => "Unknown action: {$action}",
        ];
    }

    /**
     * Формирует ответ для Click в формате JSON.
     */
    public static function formatResponse(int $error, string $errorNote, string $clickTransId, string $merchantTransId, ?string $merchantConfirmId = null): array
    {
        $resp = [
            'click_trans_id'    => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'error'             => $error,
            'error_note'        => $errorNote,
        ];

        if ($merchantConfirmId !== null) {
            $resp['merchant_confirm_id'] = $merchantConfirmId;
        }

        return $resp;
    }
}
