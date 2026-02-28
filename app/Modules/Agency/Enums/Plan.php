<?php

namespace App\Modules\Agency\Enums;

enum Plan: string
{
    case Trial      = 'trial';
    case Starter    = 'starter';
    case Pro        = 'pro';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match($this) {
            Plan::Trial      => 'Trial',
            Plan::Starter    => 'Starter',
            Plan::Pro        => 'Pro',
            Plan::Enterprise => 'Enterprise',
        };
    }

    public function trialDays(): int
    {
        return 30;
    }

    public function maxManagers(): int
    {
        return match($this) {
            Plan::Trial, Plan::Starter => 3,
            Plan::Pro                  => 10,
            Plan::Enterprise           => PHP_INT_MAX,
        };
    }

    public function maxCases(): int
    {
        return match($this) {
            Plan::Trial, Plan::Starter => 50,
            Plan::Pro                  => 200,
            Plan::Enterprise           => PHP_INT_MAX,
        };
    }

    public function hasMarketplace(): bool
    {
        return match($this) {
            Plan::Trial, Plan::Starter => false,
            Plan::Pro, Plan::Enterprise => true,
        };
    }
}
