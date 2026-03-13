<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Services\AgencyService;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Modules\Group\Services\GroupService;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\PublicPortal\Models\PublicLead;
use App\Modules\PublicPortal\Models\PublicUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\Helpers\AuditLog;

class ClientPaymentService
{
    /**
     * Создать платёж для кейса.
     */
    public function createPayment(VisaCase $case, PublicUser $publicUser, string $provider): ClientPayment
    {
        return DB::transaction(function () use ($case, $publicUser, $provider) {
            if ($case->agency_id && DB::connection()->getDriverName() !== 'sqlite') {
                DB::unprepared("SET LOCAL app.current_tenant_id = '{$case->agency_id}'");
            }

            $package = $case->agency_id
                ? DB::table('agency_service_packages')
                    ->where('agency_id', $case->agency_id)
                    ->where('country_code', $case->country_code)
                    ->where('visa_type', $case->visa_type)
                    ->where('is_active', true)
                    ->first()
                : null;

            $basePrice = (int) ($package?->price ?? 0);
            $currency  = $package?->currency ?? 'USD';

            // Разбивка: заявитель (100%) + семья (дети -50%, остальные -25%)
            $breakdown = [];
            $breakdown[] = [
                'name'         => $publicUser->name ?? 'Заявитель',
                'role'         => 'applicant',
                'discount'     => 0,
                'price'        => $basePrice,
            ];

            $familyMembers = DB::table('case_family_members')
                ->join('public_user_family_members', 'case_family_members.family_member_id', '=', 'public_user_family_members.id')
                ->where('case_family_members.case_id', $case->id)
                ->select('public_user_family_members.name', 'public_user_family_members.relationship')
                ->get();

            foreach ($familyMembers as $fm) {
                $isChild  = $fm->relationship === 'child';
                $discount = $isChild ? 50 : 25;
                $price    = (int) round($basePrice * (100 - $discount) / 100);
                $breakdown[] = [
                    'name'         => $fm->name,
                    'role'         => $fm->relationship,
                    'discount'     => $discount,
                    'price'        => $price,
                ];
            }

            $amount = array_sum(array_column($breakdown, 'price'));

            $payment = ClientPayment::create([
                'case_id'        => $case->id,
                'public_user_id' => $publicUser->id,
                'agency_id'      => $case->agency_id,
                'package_id'     => $package->id ?? null,
                'amount'         => $amount,
                'currency'       => $currency,
                'provider'       => $provider,
                'status'         => 'pending',
                'expires_at'     => now()->addDays(5),
                'metadata'       => ['price_breakdown' => $breakdown, 'base_price' => $basePrice],
            ]);

            $case->update(['payment_status' => 'pending']);

            return $payment;
        });
    }

    /**
     * Пересчитать сумму pending-платежа с учётом членов семьи.
     * Вызывается при attach/detach семьи и при загрузке биллинга (catch-up).
     */
    public static function recalculatePaymentAmount(ClientPayment $payment): void
    {
        if ($payment->status !== 'pending' || ! $payment->case_id) return;

        // lockForUpdate для предотвращения race condition при параллельных attach/detach (#12)
        DB::transaction(function () use (&$payment) {
            $payment = ClientPayment::where('id', $payment->id)->lockForUpdate()->first();
            if (!$payment || $payment->status !== 'pending') return;

            static::doRecalculate($payment);
        });
    }

    private static function doRecalculate(ClientPayment $payment): void
    {
        // Базовая цена: из metadata или из пакета
        $basePrice = $payment->metadata['base_price'] ?? null;

        if ($basePrice === null) {
            // Первый раз — берём цену пакета
            if ($payment->package_id) {
                $basePrice = (int) DB::table('agency_service_packages')
                    ->where('id', $payment->package_id)
                    ->value('price');
            }
            if (! $basePrice) {
                $basePrice = $payment->amount;
            }
        }

        $basePrice = (int) $basePrice;

        // Имя заявителя
        $applicantName = null;
        if ($payment->public_user_id) {
            $applicantName = DB::table('public_users')
                ->where('id', $payment->public_user_id)
                ->value('name');
        }

        $breakdown = [];
        $breakdown[] = [
            'name'     => $applicantName ?? 'Заявитель',
            'role'     => 'applicant',
            'discount' => 0,
            'price'    => $basePrice,
        ];

        $familyMembers = DB::table('case_family_members')
            ->join('public_user_family_members', 'case_family_members.family_member_id', '=', 'public_user_family_members.id')
            ->where('case_family_members.case_id', $payment->case_id)
            ->select('public_user_family_members.name', 'public_user_family_members.relationship')
            ->get();

        foreach ($familyMembers as $fm) {
            $isChild  = $fm->relationship === 'child';
            $discount = $isChild ? 50 : 25;
            $price    = (int) round($basePrice * (100 - $discount) / 100);
            $breakdown[] = [
                'name'     => $fm->name,
                'role'     => $fm->relationship,
                'discount' => $discount,
                'price'    => $price,
            ];
        }

        $amount = array_sum(array_column($breakdown, 'price'));

        $metadata = $payment->metadata ?? [];
        $metadata['price_breakdown'] = $breakdown;
        $metadata['base_price'] = $basePrice;

        $payment->update([
            'amount'   => $amount,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Сгенерировать URL для оплаты (заглушка — реальная интеграция позже).
     */
    public function getPaymentUrl(ClientPayment $payment): string
    {
        // TODO: реальная интеграция с Click/Payme/Uzum
        return match ($payment->provider) {
            'click' => "https://my.click.uz/services/pay?amount={$payment->amount}&transaction_id={$payment->id}",
            'payme' => "https://checkout.paycom.uz/{$payment->id}",
            'uzum'  => "https://www.uzumbank.uz/pay?id={$payment->id}",
            default => '#',
        };
    }

    /**
     * Обработать callback от платёжной системы.
     */
    public function handleCallback(string $provider, array $data): ?ClientPayment
    {
        $paymentId = $data['payment_id'] ?? $data['transaction_id'] ?? null;
        if (! $paymentId) return null;

        $payment = ClientPayment::find($paymentId);
        if (! $payment) return null;

        // Идемпотентность: проверяем по provider_transaction_id ИЛИ по payment_id + status
        $providerTxnId = $data['provider_transaction_id'] ?? null;

        if ($payment->status === 'succeeded') {
            Log::channel('billing')->info('Webhook skipped: payment already succeeded', [
                'provider'   => $provider,
                'payment_id' => $payment->id,
            ]);
            return $payment;
        }

        if ($providerTxnId) {
            $existing = ClientPayment::where('provider', $provider)
                ->where('provider_transaction_id', $providerTxnId)
                ->where('status', 'succeeded')
                ->first();

            if ($existing) {
                Log::channel('billing')->info('Webhook idempotency hit: already processed', [
                    'provider'               => $provider,
                    'provider_transaction_id' => $providerTxnId,
                    'payment_id'             => $existing->id,
                ]);
                return $existing;
            }
        } else {
            // NULL provider_transaction_id — логируем предупреждение,
            // полагаемся на lockForUpdate + status check внутри транзакции
            Log::channel('billing')->warning('Webhook without provider_transaction_id — relying on lock-based idempotency', [
                'provider'   => $provider,
                'payment_id' => $payment->id,
            ]);
        }

        // Проверка истечения срока платежа
        if ($payment->expires_at && $payment->expires_at->isPast()) {
            Log::channel('billing')->warning('Webhook rejected: payment expired', [
                'provider'   => $provider,
                'payment_id' => $payment->id,
                'expires_at' => $payment->expires_at->toIso8601String(),
            ]);
            $payment->update(['status' => 'expired']);
            return null;
        }

        try {
            DB::transaction(function () use ($payment, $provider, $data) {
                if ($payment->agency_id) {
                    if (DB::connection()->getDriverName() !== 'sqlite') {
                        DB::unprepared("SET LOCAL app.current_tenant_id = '{$payment->agency_id}'");
                    }
                }

                // SELECT FOR UPDATE для предотвращения race condition
                $lockedPayment = ClientPayment::where('id', $payment->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedPayment || in_array($lockedPayment->status, ['succeeded', 'expired'])) {
                    return;
                }

                // Повторная проверка expires_at под блокировкой
                if ($lockedPayment->expires_at && $lockedPayment->expires_at->isPast()) {
                    $lockedPayment->update(['status' => 'expired']);
                    Log::channel('billing')->warning('Payment expired during processing', [
                        'payment_id' => $lockedPayment->id,
                    ]);
                    return;
                }

                // Если provider_transaction_id не пришел — генерируем fallback для идемпотентности
                $txnId = $data['provider_transaction_id']
                    ?? 'auto_' . $provider . '_' . $lockedPayment->id . '_' . now()->timestamp;

                $lockedPayment->update([
                    'status'                  => 'succeeded',
                    'provider_transaction_id' => $txnId,
                    'paid_at'                 => now(),
                    'metadata'                => array_merge($lockedPayment->metadata ?? [], $data),
                ]);

                if ($lockedPayment->group_id) {
                    app(GroupService::class)->handleGroupPaymentSuccess($lockedPayment);
                } else {
                    $case = VisaCase::find($lockedPayment->case_id);
                    if (! $case) return;

                    $caseService = app(CaseService::class);

                    // Обновить payment_status
                    $case->update([
                        'payment_status' => 'paid',
                        'public_status'  => 'submitted',
                        'stage'          => 'lead',
                    ]);

                    // Создать запись в case_stages (SLA + история) если её нет
                    $hasStageEntry = \App\Modules\Case\Models\CaseStage::where('case_id', $case->id)
                        ->where('stage', 'lead')
                        ->exists();
                    if (! $hasStageEntry) {
                        $stageEntry = \App\Modules\Case\Models\CaseStage::create([
                            'case_id'    => $case->id,
                            'user_id'    => null,
                            'stage'      => 'lead',
                            'entered_at' => now(),
                            'notes'      => 'Оплата подтверждена',
                        ]);
                        app(\App\Modules\Workflow\Services\SlaService::class)->applyStageSla($stageEntry, $case);
                    }

                    // Создать PublicLead
                    $score = (int) DB::table('public_score_cache')
                        ->where('public_user_id', $case->client?->public_user_id)
                        ->where('country_code', $case->country_code)
                        ->value('score');

                    $lead = PublicLead::create([
                        'public_user_id'     => $case->client?->public_user_id,
                        'country_code'       => $case->country_code,
                        'visa_type'          => $case->visa_type,
                        'score'              => $score,
                        'status'             => 'new',
                        'assigned_agency_id' => $case->agency_id,
                        'case_id'            => $case->id,
                        'client_id'          => $case->client_id,
                    ]);

                    // Авто-назначение менеджера по стратегии агентства
                    if ($case->agency_id) {
                        $agency = Agency::find($case->agency_id);
                        if ($agency && $agency->lead_assignment_mode !== 'manual') {
                            $manager = app(AgencyService::class)->assignLead($lead, $agency);
                            if ($manager) {
                                $case->refresh();
                                $case->update([
                                    'assigned_to'    => $manager->id,
                                    'public_status'  => 'manager_assigned',
                                ]);
                                // Переход lead -> qualification через CaseService (SLA + история)
                                $caseService->moveToStageSystem($case->fresh(), 'qualification', 'Менеджер назначен автоматически');
                                $lead->update(['status' => 'assigned']);
                            }
                        }
                    }
                }
            });

            Log::channel('billing')->info('Webhook processed successfully', [
                'provider'               => $provider,
                'payment_id'             => $payment->id,
                'provider_transaction_id' => $providerTxnId,
            ]);

            AuditLog::log('payment.webhook_processed', [
                'provider'               => $provider,
                'payment_id'             => $payment->id,
                'provider_transaction_id' => $providerTxnId,
            ]);

            return $payment->fresh();

        } catch (\Throwable $e) {
            // Dead-letter: логируем ошибку, но не меняем статус платежа (оставляем pending для retry)
            Log::channel('billing')->error('Webhook processing failed', [
                'provider'               => $provider,
                'payment_id'             => $payment->id,
                'provider_transaction_id' => $providerTxnId,
                'error'                  => $e->getMessage(),
                'trace'                  => $e->getTraceAsString(),
            ]);

            AuditLog::log('payment.webhook_failed', [
                'provider'               => $provider,
                'payment_id'             => $payment->id,
                'provider_transaction_id' => $providerTxnId,
                'error'                  => $e->getMessage(),
            ]);

            // Возвращаем null, но не бросаем исключение — контроллер вернёт 200
            return null;
        }
    }
}
