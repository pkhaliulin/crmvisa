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

class ClientPaymentService
{
    /**
     * Создать платёж для кейса.
     */
    public function createPayment(VisaCase $case, PublicUser $publicUser, string $provider): ClientPayment
    {
        return DB::transaction(function () use ($case, $publicUser, $provider) {
            if ($case->agency_id) {
                DB::statement("SET LOCAL app.current_tenant_id = '{$case->agency_id}'");
            }

            $package = $case->agency_id
                ? DB::table('agency_service_packages')
                    ->where('agency_id', $case->agency_id)
                    ->where('country_code', $case->country_code)
                    ->where('visa_type', $case->visa_type)
                    ->where('is_active', true)
                    ->first()
                : null;

            $basePrice = (int) ($package->price ?? 0);
            $currency  = $package->currency ?? 'USD';

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
    public function handleCallback(string $provider, array $data): void
    {
        // TODO: реальная обработка webhook от Click/Payme/Uzum
        $paymentId = $data['payment_id'] ?? $data['transaction_id'] ?? null;
        if (! $paymentId) return;

        $payment = ClientPayment::find($paymentId);
        if (! $payment) return;

        DB::transaction(function () use ($payment, $provider, $data) {
            if ($payment->agency_id) {
                DB::statement("SET LOCAL app.current_tenant_id = '{$payment->agency_id}'");
            }

            $payment->update([
                'status'                 => 'succeeded',
                'provider_transaction_id' => $data['provider_transaction_id'] ?? null,
                'paid_at'                => now(),
                'metadata'               => array_merge($payment->metadata ?? [], $data),
            ]);

            if ($payment->group_id) {
                app(GroupService::class)->handleGroupPaymentSuccess($payment);
            } else {
                $case = VisaCase::find($payment->case_id);
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
    }
}
