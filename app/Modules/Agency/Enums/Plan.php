<?php

namespace App\Modules\Agency\Enums;

use App\Modules\Payment\Models\BillingPlan;

enum Plan: string
{
    case Trial      = 'trial';
    case Starter    = 'starter';
    case Pro        = 'pro';
    case Business   = 'business';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match($this) {
            Plan::Trial      => 'Trial',
            Plan::Starter    => 'Starter',
            Plan::Pro        => 'Pro',
            Plan::Business   => 'Business',
            Plan::Enterprise => 'Enterprise',
        };
    }

    public function trialDays(): int
    {
        $bp = $this->billingPlan();
        if ($bp && $bp->trial_days > 0) {
            return $bp->trial_days;
        }

        return 45;
    }

    /**
     * Все лимиты и фичи берутся из billing_plans (SSOT).
     * Методы ниже — fallback на случай, если запись в billing_plans отсутствует.
     */
    public function billingPlan(): ?BillingPlan
    {
        return BillingPlan::find($this->value);
    }

    public function maxManagers(): int
    {
        $bp = $this->billingPlan();
        if ($bp) {
            return $bp->max_managers === 0 ? PHP_INT_MAX : $bp->max_managers;
        }

        return match($this) {
            Plan::Trial, Plan::Starter => 1,
            Plan::Pro                  => 4,
            Plan::Business             => 10,
            Plan::Enterprise           => PHP_INT_MAX,
        };
    }

    public function maxCases(): int
    {
        $bp = $this->billingPlan();
        if ($bp) {
            return $bp->max_cases === 0 ? PHP_INT_MAX : $bp->max_cases;
        }

        return match($this) {
            Plan::Trial, Plan::Starter => 50,
            Plan::Pro                  => 200,
            Plan::Business             => 500,
            Plan::Enterprise           => PHP_INT_MAX,
        };
    }

    public function hasMarketplace(): bool
    {
        $bp = $this->billingPlan();
        if ($bp) {
            return $bp->has_marketplace;
        }

        return match($this) {
            Plan::Trial, Plan::Starter => false,
            Plan::Pro, Plan::Business, Plan::Enterprise => true,
        };
    }
}
