<?php

namespace App\Modules\Payment\Services\Gateways;

use App\Modules\Payment\Services\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UzumGateway implements PaymentGateway
{
    private string $terminalId;
    private string $secretKey;
    private string $serviceId;
    private string $baseUrl = 'https://api.uzum.uz/merchant/v1';

    public function __construct()
    {
        $this->terminalId = config('services.uzum.terminal_id', '');
        $this->secretKey  = config('services.uzum.secret_key', '');
        $this->serviceId  = config('services.uzum.service_id', '');
    }

    /**
     * {@inheritDoc}
     *
     * Uzum Business: создаёт платёж через REST API.
     */
    public function createPayment(float $amount, string $orderId, array $params = []): array
    {
        $amountInTiyin = (int) ($amount * 100);
        $returnUrl     = $params['return_url'] ?? config('app.url') . '/billing?payment=success';

        if (empty($this->terminalId) || empty($this->secretKey)) {
            // Dev-режим: формируем stub URL
            $checkoutUrl = "https://www.uzumbank.uz/pay?" . http_build_query([
                'terminal_id' => $this->terminalId,
                'order_id'    => $orderId,
                'amount'      => $amountInTiyin,
            ]);

            Log::channel('billing')->info('Uzum: stub payment URL generated (dev mode)', [
                'order_id' => $orderId,
                'amount'   => $amount,
            ]);

            return [
                'checkout_url' => $checkoutUrl,
                'external_id'  => null,
                'raw'          => ['dev_mode' => true],
            ];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/payment/create", [
                    'terminal_id' => $this->terminalId,
                    'service_id'  => $this->serviceId,
                    'order_id'    => $orderId,
                    'amount'      => $amountInTiyin,
                    'currency'    => 860, // ISO 4217: UZS
                    'description' => $params['description'] ?? 'CRM Visa subscription payment',
                    'return_url'  => $returnUrl,
                ]);

            $body = $response->json();

            if (! $response->successful() || isset($body['error'])) {
                Log::channel('billing')->error('Uzum: createPayment failed', [
                    'order_id' => $orderId,
                    'response' => $body,
                    'status'   => $response->status(),
                ]);

                return [
                    'checkout_url' => '',
                    'external_id'  => null,
                    'raw'          => $body,
                ];
            }

            $checkoutUrl = $body['data']['payment_url'] ?? $body['payment_url'] ?? '';
            $externalId  = $body['data']['transaction_id'] ?? $body['transaction_id'] ?? null;

            Log::channel('billing')->info('Uzum: payment created', [
                'order_id'    => $orderId,
                'external_id' => $externalId,
                'amount'      => $amount,
            ]);

            return [
                'checkout_url' => $checkoutUrl,
                'external_id'  => $externalId,
                'raw'          => $body,
            ];
        } catch (\Throwable $e) {
            Log::channel('billing')->error('Uzum: createPayment exception', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'checkout_url' => '',
                'external_id'  => null,
                'raw'          => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkStatus(string $transactionId): array
    {
        if (empty($this->secretKey)) {
            return [
                'status'      => 'unknown',
                'external_id' => $transactionId,
                'amount'      => 0,
                'raw'         => ['error' => 'Credentials not configured'],
            ];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/payment/status", [
                    'transaction_id' => $transactionId,
                ]);

            $body   = $response->json();
            $data   = $body['data'] ?? $body;
            $state  = $data['status'] ?? $data['state'] ?? 'unknown';
            $amount = (($data['amount'] ?? 0) / 100);

            $status = match ((string) $state) {
                'PAID', 'SUCCESS', '2'          => 'completed',
                'CREATED', 'PENDING', '0', '1'  => 'pending',
                'CANCELLED', 'FAILED', '-1'     => 'failed',
                default                         => 'unknown',
            };

            return [
                'status'      => $status,
                'external_id' => $transactionId,
                'amount'      => $amount,
                'raw'         => $body,
            ];
        } catch (\Throwable $e) {
            Log::channel('billing')->error('Uzum: checkStatus failed', [
                'transaction_id' => $transactionId,
                'error'          => $e->getMessage(),
            ]);
            return [
                'status'      => 'error',
                'external_id' => $transactionId,
                'amount'      => 0,
                'raw'         => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handleCallback(array $data): array
    {
        $transactionId = $data['transaction_id'] ?? $data['transactionId'] ?? null;
        $orderId       = $data['order_id'] ?? $data['merchant_trans_id'] ?? null;
        $amount        = (float) (($data['amount'] ?? 0) / 100);
        $status        = $data['status'] ?? $data['state'] ?? '';

        // Проверка подписи (если есть sign)
        if (! empty($this->secretKey) && isset($data['sign'])) {
            $signData = ($transactionId ?? '') . ($orderId ?? '') . ($data['amount'] ?? '') . $this->secretKey;
            $expectedSign = hash('sha256', $signData);

            if (! hash_equals($expectedSign, $data['sign'])) {
                Log::channel('billing')->warning('Uzum: invalid signature', [
                    'transaction_id' => $transactionId,
                    'order_id'       => $orderId,
                ]);
                return [
                    'success'     => false,
                    'external_id' => $transactionId,
                    'order_id'    => $orderId,
                    'amount'      => $amount,
                    'error'       => 'Invalid signature',
                ];
            }
        }

        $success = in_array((string) $status, ['PAID', 'SUCCESS', 'completed', '2']);

        Log::channel('billing')->info('Uzum: callback processed', [
            'transaction_id' => $transactionId,
            'order_id'       => $orderId,
            'status'         => $status,
            'success'        => $success,
            'amount'         => $amount,
        ]);

        return [
            'success'     => $success,
            'external_id' => $transactionId,
            'order_id'    => $orderId,
            'amount'      => $amount,
            'error'       => $success ? null : "Payment status: {$status}",
        ];
    }

    /**
     * Проверить подпись webhook от Uzum.
     */
    public static function verifySignature(array $data): bool
    {
        $secretKey = config('services.uzum.secret_key', '');

        if (empty($secretKey)) {
            return true; // Dev-режим
        }

        if (! isset($data['sign'])) {
            return true; // Uzum не всегда отправляет sign
        }

        $transactionId = $data['transaction_id'] ?? $data['transactionId'] ?? '';
        $orderId       = $data['order_id'] ?? $data['merchant_trans_id'] ?? '';
        $amount        = $data['amount'] ?? '';

        $signData     = $transactionId . $orderId . $amount . $secretKey;
        $expectedSign = hash('sha256', $signData);

        return hash_equals($expectedSign, $data['sign']);
    }
}
