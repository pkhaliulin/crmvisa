<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencyAddon;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\AgencyWallet;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;

class BillingHelperService
{
    public function calculateVat(int $amount): array
    {
        $enabled = PlatformSetting::get('vat_enabled', false);
        if (! $enabled) {
            return ['vat' => 0, 'rate' => 0];
        }

        $rate = (float) PlatformSetting::get('vat_rate', 12);
        $vat  = (int) round($amount * $rate / 100);

        return ['vat' => $vat, 'rate' => $rate];
    }

    public function createSubscriptionInvoice(AgencySubscription $subscription, BillingPlan $plan, int $discount): Invoice
    {
        $price    = $subscription->billing_period === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
        $subtotal = max(0, $price - $discount);
        $vatInfo  = $this->calculateVat($subtotal);

        return Invoice::create([
            'agency_id'       => $subscription->agency_id,
            'subscription_id' => $subscription->id,
            'number'          => Invoice::generateNumber(),
            'type'            => 'subscription',
            'status'          => 'issued',
            'subtotal'        => $subtotal,
            'vat_amount'      => $vatInfo['vat'],
            'discount_amount' => $discount,
            'total'           => $subtotal + $vatInfo['vat'],
            'currency'        => 'UZS',
            'line_items'      => [[
                'description' => "{$plan->name} — {$subscription->billing_period}",
                'quantity'    => 1,
                'unit_price'  => $price,
                'discount'    => $discount,
                'total'       => $subtotal,
            ]],
            'issued_at'       => now(),
            'due_at'          => now()->addDays(7),
        ]);
    }

    public function getWallet(Agency $agency): AgencyWallet
    {
        return AgencyWallet::firstOrCreate(
            ['agency_id' => $agency->id],
            ['balance' => 0, 'total_earned' => 0, 'total_deducted' => 0, 'total_paid_out' => 0, 'pending_payout' => 0]
        );
    }

    public function checkLimits(Agency $agency): array
    {
        $planValue = $agency->plan instanceof \BackedEnum ? $agency->plan->value : (string) $agency->plan;
        $plan      = BillingPlan::find($planValue);

        if (! $plan) {
            return ['valid' => false, 'reason' => 'Plan not found'];
        }

        // Аддоны
        $extraManagers = 0;
        $extraCases    = 0;
        $extraLeads    = 0;

        $activeAddons = AgencyAddon::where('agency_id', $agency->id)->where('status', 'active')->get();
        foreach ($activeAddons as $aa) {
            $limits = $aa->addon?->limits ?? [];
            $extraManagers += $limits['extra_managers'] ?? 0;
            $extraCases    += $limits['extra_cases'] ?? 0;
            $extraLeads    += $limits['extra_leads'] ?? 0;
        }

        $maxManagers = $plan->max_managers === 0 ? null : $plan->max_managers + $extraManagers;
        $maxCases    = $plan->max_cases === 0 ? null : $plan->max_cases + $extraCases;
        $maxLeads    = $plan->max_leads_per_month === 0 ? null : $plan->max_leads_per_month + $extraLeads;

        $managersCount = $agency->users()->whereIn('role', ['owner', 'manager'])->count();
        $casesCount    = \App\Modules\Case\Models\VisaCase::where('agency_id', $agency->id)
            ->whereNotIn('stage', ['result'])
            ->count();

        // Лиды за текущий месяц
        $leadsThisMonth = DB::table('public_leads')
            ->where('assigned_agency_id', $agency->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'valid'           => true,
            'managers_count'  => $managersCount,
            'max_managers'    => $maxManagers,
            'cases_count'     => $casesCount,
            'max_cases'       => $maxCases,
            'leads_count'     => $leadsThisMonth,
            'max_leads'       => $maxLeads,
            'has_marketplace' => $plan->has_marketplace,
            'has_api_access'  => $plan->has_api_access,
            'can_add_manager' => $maxManagers === null || $managersCount < $maxManagers,
            'can_add_case'    => $maxCases === null || $casesCount < $maxCases,
            'can_accept_lead' => $maxLeads === null || $leadsThisMonth < $maxLeads,
        ];
    }

    public function processExpiredSubscriptions(): int
    {
        $subscriptionService = app(SubscriptionService::class);
        $count = 0;

        // Подписки, у которых истёк срок и grace-period
        $expired = AgencySubscription::whereIn('status', ['active', 'trialing', 'past_due'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where(function ($q) {
                $q->whereNull('grace_ends_at')->orWhere('grace_ends_at', '<', now());
            })
            ->get();

        foreach ($expired as $sub) {
            if ($sub->auto_renew && $sub->payment_model === 'earn_first') {
                // Earn-first: автопродление без оплаты
                $subscriptionService->renewSubscription($sub);
            } else {
                $sub->update(['status' => 'expired']);
                $sub->agency?->update(['plan_expires_at' => now()]);
                \App\Modules\Payment\Models\BillingEvent::log('subscription.expired', $sub->agency_id, null, 'agency_subscription', $sub->id);
            }
            $count++;
        }

        // Подписки в grace-period — отметить past_due
        AgencySubscription::whereIn('status', ['active', 'trialing'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '>', now())
            ->update(['status' => 'past_due']);

        return $count;
    }
}
