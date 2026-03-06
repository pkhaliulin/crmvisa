<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyWallet extends BaseModel
{
    use HasUuid;

    protected $fillable = [
        'agency_id',
        'balance',
        'total_earned',
        'total_deducted',
        'total_paid_out',
        'pending_payout',
        'last_payout_at',
    ];

    protected $casts = [
        'balance'         => 'integer',
        'total_earned'    => 'integer',
        'total_deducted'  => 'integer',
        'total_paid_out'  => 'integer',
        'pending_payout'  => 'integer',
        'last_payout_at'  => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function credit(int $amount, string $reason = ''): void
    {
        $this->increment('balance', $amount);
        $this->increment('total_earned', $amount);
    }

    public function deduct(int $amount, string $reason = ''): void
    {
        $this->decrement('balance', $amount);
        $this->increment('total_deducted', $amount);
    }

    public function payout(int $amount): void
    {
        $this->decrement('balance', $amount);
        $this->decrement('pending_payout', $amount);
        $this->increment('total_paid_out', $amount);
        $this->update(['last_payout_at' => now()]);
    }
}
