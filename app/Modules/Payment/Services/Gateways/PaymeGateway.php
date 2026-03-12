<?php

namespace App\Modules\Payment\Services\Gateways;

use App\Modules\Payment\Services\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymeGateway implements PaymentGateway
{
    private string $merchantId;
    private string $key;
    private string $testKey;
    private string $baseUrl = 'https://checkout.paycom.uz';

    public function __construct()
    {
        $this->merchantId = config('services.payme.merchant_id', '');
        $this->key        = config('services.payme.key', '');
        $this->testKey    = config('services.payme.test_key', '');
    }

    /**
     * {@inheritDoc}
     *
     * Payme: формирует URL для редиректа на checkout.
     * Сумма передаётся в тийинах (x100).
     */
    public function createPayment(float $amount, string $orderId, array $params = []): array
    {
        // Payme checkout URL: base64 encode параметров
        $amountInTiyin = (int) ($amount * 100);

        $paymeParams = [
            'm' => $this->merchantId,
            'ac' => ['order_id' => $orderId],
            'a'  => $amountInTiyin,
            'c'  => $params['return_url'] ?? config('app.url') . '/billing?payment=success',
        ];

        $encoded = base64_encode(json_encode($paymeParams));
        $checkoutUrl = "{$this->baseUrl}/{$encoded}";

        Log::channel('billing')->info('Payme: payment created', [
            'order_id'       => $orderId,
            'amount'         => $amount,
            'amount_tiyin'   => $amountInTiyin,
        ]);

        return [
            'checkout_url' => $checkoutUrl,
            'external_id'  => null,
            'raw'          => ['merchant_id' => $this->merchantId, 'amount_tiyin' => $amountInTiyin],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Payme JSON-RPC: CheckTransaction
     */
    public function checkStatus(string $transactionId): array
    {
        $activeKey = $this->getActiveKey();

        if (empty($this->merchantId) || empty($activeKey)) {
            return [
                'status'      => 'unknown',
                'external_id' => $transactionId,
                'amount'      => 0,
                'raw'         => ['error' => 'Credentials not configured'],
            ];
        }

        try {
            $response = Http::withBasicAuth($this->merchantId, $activeKey)
                ->post("{$this->baseUrl}/api", [
                    'id'     => time(),
                    'method' => 'CheckTransaction',
                    'params' => [
                        'id' => $transactionId,
                    ],
                ]);

            $body = $response->json();

            if (isset($body['error'])) {
                return [
                    'status'      => 'error',
                    'external_id' => $transactionId,
                    'amount'      => 0,
                    'raw'         => $body,
                ];
            }

            $result = $body['result'] ?? [];
            $state  = $result['state'] ?? 0;

            $status = match ($state) {
                1       => 'processing',
                2       => 'completed',
                -1      => 'cancelled',
                -2      => 'cancelled',
                default => 'pending',
            };

            return [
                'status'      => $status,
                'external_id' => $transactionId,
                'amount'      => ($result['amount'] ?? 0) / 100, // из тийинов в сумы
                'raw'         => $body,
            ];
        } catch (\Throwable $e) {
            Log::channel('billing')->error('Payme: checkStatus failed', [
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
     *
     * Payme JSON-RPC callback: обрабатывает CheckPerformTransaction, CreateTransaction,
     * PerformTransaction, CancelTransaction, CheckTransaction.
     */
    public function handleCallback(array $data): array
    {
        $method = $data['method'] ?? '';
        $params = $data['params'] ?? [];
        $id     = $data['id'] ?? null;

        Log::channel('billing')->info('Payme: callback received', [
            'method' => $method,
            'params' => $params,
        ]);

        // Проверка авторизации (Basic auth) выполняется в контроллере
        return match ($method) {
            'CheckPerformTransaction'  => $this->handleCheckPerform($id, $params),
            'CreateTransaction'        => $this->handleCreateTransaction($id, $params),
            'PerformTransaction'       => $this->handlePerformTransaction($id, $params),
            'CancelTransaction'        => $this->handleCancelTransaction($id, $params),
            'CheckTransaction'         => $this->handleCheckTransaction($id, $params),
            default => [
                'success'     => false,
                'external_id' => null,
                'order_id'    => null,
                'amount'      => null,
                'error'       => "Unknown method: {$method}",
                'jsonrpc_response' => self::errorResponse($id, -32601, 'Method not found'),
            ],
        };
    }

    private function handleCheckPerform(mixed $id, array $params): array
    {
        $orderId = $params['account']['order_id'] ?? null;
        $amount  = ($params['amount'] ?? 0) / 100;

        return [
            'success'     => true,
            'external_id' => null,
            'order_id'    => $orderId,
            'amount'      => $amount,
            'error'       => null,
            'method'      => 'CheckPerformTransaction',
            'jsonrpc_response' => [
                'id'     => $id,
                'result' => ['allow' => true],
            ],
        ];
    }

    private function handleCreateTransaction(mixed $id, array $params): array
    {
        $paymeTransId = $params['id'] ?? null;
        $orderId      = $params['account']['order_id'] ?? null;
        $amount       = ($params['amount'] ?? 0) / 100;
        $time         = $params['time'] ?? (time() * 1000);

        return [
            'success'     => true,
            'external_id' => $paymeTransId,
            'order_id'    => $orderId,
            'amount'      => $amount,
            'error'       => null,
            'method'      => 'CreateTransaction',
            'jsonrpc_response' => [
                'id'     => $id,
                'result' => [
                    'create_time' => $time,
                    'transaction' => $orderId,
                    'state'       => 1,
                ],
            ],
        ];
    }

    private function handlePerformTransaction(mixed $id, array $params): array
    {
        $paymeTransId = $params['id'] ?? null;

        return [
            'success'     => true,
            'external_id' => $paymeTransId,
            'order_id'    => null,
            'amount'      => null,
            'error'       => null,
            'method'      => 'PerformTransaction',
            'jsonrpc_response' => [
                'id'     => $id,
                'result' => [
                    'transaction'  => $paymeTransId,
                    'perform_time' => time() * 1000,
                    'state'        => 2,
                ],
            ],
        ];
    }

    private function handleCancelTransaction(mixed $id, array $params): array
    {
        $paymeTransId = $params['id'] ?? null;
        $reason       = $params['reason'] ?? null;

        return [
            'success'     => false,
            'external_id' => $paymeTransId,
            'order_id'    => null,
            'amount'      => null,
            'error'       => "Cancelled: reason {$reason}",
            'method'      => 'CancelTransaction',
            'jsonrpc_response' => [
                'id'     => $id,
                'result' => [
                    'transaction' => $paymeTransId,
                    'cancel_time' => time() * 1000,
                    'state'       => -1,
                ],
            ],
        ];
    }

    private function handleCheckTransaction(mixed $id, array $params): array
    {
        $paymeTransId = $params['id'] ?? null;

        return [
            'success'     => true,
            'external_id' => $paymeTransId,
            'order_id'    => null,
            'amount'      => null,
            'error'       => null,
            'method'      => 'CheckTransaction',
            'jsonrpc_response' => [
                'id'     => $id,
                'result' => [
                    'create_time'  => 0,
                    'perform_time' => 0,
                    'cancel_time'  => 0,
                    'transaction'  => $paymeTransId,
                    'state'        => 1,
                    'reason'       => null,
                ],
            ],
        ];
    }

    private function getActiveKey(): string
    {
        if (config('services.payme.test_mode', false)) {
            return $this->testKey ?: $this->key;
        }
        return $this->key;
    }

    public static function errorResponse(mixed $id, int $code, string $message): array
    {
        return [
            'id'    => $id,
            'error' => [
                'code'    => $code,
                'message' => ['ru' => $message, 'uz' => $message, 'en' => $message],
            ],
        ];
    }

    /**
     * Проверить Basic auth header от Payme.
     */
    public static function verifyAuth(string $authHeader): bool
    {
        $merchantId  = config('services.payme.merchant_id', '');
        $merchantKey = config('services.payme.key', '');

        if (empty($merchantId) || empty($merchantKey)) {
            // Dev-режим — пропускаем
            return true;
        }

        if (! str_starts_with($authHeader, 'Basic ')) {
            return false;
        }

        $decoded  = base64_decode(substr($authHeader, 6));
        $expected = $merchantId . ':' . $merchantKey;

        return hash_equals($expected, $decoded);
    }
}
