<?php

namespace App\Modules\Payment\Jobs;

use App\Modules\Payment\Services\ClientPaymentService;
use App\Support\Traits\HasTenantJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Асинхронная обработка webhook от платёжной системы (#23).
 * Контроллер сразу возвращает 200, обработка идёт в очереди.
 */
class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantJob;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly string $provider,
        private readonly array $data,
    ) {
        $this->onQueue('payments');
        // Webhook — superadmin context (агентство определяется по payment_id внутри)
        $this->captureTenant(null);
    }

    public function handle(ClientPaymentService $service): void
    {
        $this->setTenantContext();
        $service->handleCallback($this->provider, $this->data);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('billing')->error('ProcessWebhookJob failed permanently', [
            'provider'   => $this->provider,
            'payment_id' => $this->data['payment_id'] ?? $this->data['transaction_id'] ?? null,
            'error'      => $exception->getMessage(),
        ]);
    }
}
