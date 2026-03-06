<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends BaseModel
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'agency_id', 'subscription_id', 'number', 'type', 'status',
        'subtotal', 'vat_amount', 'discount_amount', 'total',
        'currency', 'line_items', 'issued_at', 'due_at', 'paid_at', 'metadata',
    ];

    protected $casts = [
        'subtotal'        => 'integer',
        'vat_amount'      => 'integer',
        'discount_amount' => 'integer',
        'total'           => 'integer',
        'line_items'      => 'array',
        'metadata'        => 'array',
        'issued_at'       => 'datetime',
        'due_at'          => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'issued' && $this->due_at?->isPast();
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->count();
        return sprintf('INV-%s-%04d', $year, $last + 1);
    }
}
