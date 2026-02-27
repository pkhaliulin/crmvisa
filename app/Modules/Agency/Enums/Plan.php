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
        return 14;
    }
}
