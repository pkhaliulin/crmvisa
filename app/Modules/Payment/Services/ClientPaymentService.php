<?php

namespace App\Modules\Payment\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Modules\Group\Services\GroupService;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\PublicPortal\Models\PublicLead;
use App\Modules\PublicPortal\Models\PublicUser;
use Illuminate\Support\Facades\DB;

class ClientPaymentService
{
    /**
     * Создать платёж для кейса.
     */
    public function createPayment(VisaCase $case, PublicUser $publicUser, string $provider): ClientPayment
    {
        return DB::transaction(function () use ($case, $publicUser, $provider) {
            $package = $case->agency_id
                ? DB::table('agency_service_packages')
                    ->where('agency_id', $case->agency_id)
                    ->where('country_code', $case->country_code)
                    ->where('visa_type', $case->visa_type)
                    ->where('is_active', true)
                    ->first()
                : null;

            $amount   = $package->price ?? 0;
            $currency = $package->currency ?? 'USD';

            $payment = ClientPayment::create([
                'case_id'        => $case->id,
                'public_user_id' => $publicUser->id,
                'agency_id'      => $case->agency_id,
                'package_id'     => $package->id ?? null,
                'amount'         => $amount,
                'currency'       => $currency,
                'provider'       => $provider,
                'status'         => 'pending',
            ]);

            $case->update(['payment_status' => 'pending']);

            return $payment;
        });
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
    public function handleCallback(string $provider, array $data): void
    {
        // TODO: реальная обработка webhook от Click/Payme/Uzum
        $paymentId = $data['payment_id'] ?? $data['transaction_id'] ?? null;
        if (! $paymentId) return;

        $payment = ClientPayment::find($paymentId);
        if (! $payment) return;

        DB::transaction(function () use ($payment, $provider, $data) {
            $payment->update([
                'status'                 => 'succeeded',
                'provider_transaction_id' => $data['provider_transaction_id'] ?? null,
                'paid_at'                => now(),
                'metadata'               => $data,
            ]);

            if ($payment->group_id) {
                app(GroupService::class)->handleGroupPaymentSuccess($payment);
            } else {
                $case = VisaCase::find($payment->case_id);
                if (! $case) return;

                // Обновить payment_status и public_status
                $case->update([
                    'payment_status' => 'paid',
                    'public_status'  => 'submitted',
                ]);

                // Создать PublicLead — лид передаётся агентству только после оплаты
                $score = (int) DB::table('public_score_cache')
                    ->where('public_user_id', $case->client?->public_user_id)
                    ->where('country_code', $case->country_code)
                    ->value('score');

                PublicLead::create([
                    'public_user_id'     => $case->client?->public_user_id,
                    'country_code'       => $case->country_code,
                    'visa_type'          => $case->visa_type,
                    'score'              => $score,
                    'status'             => 'new',
                    'assigned_agency_id' => $case->agency_id,
                    'case_id'            => $case->id,
                    'client_id'          => $case->client_id,
                ]);
            }
        });
    }
}
