<?php

namespace App\Modules\Case\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CasePayment extends BaseModel
{
    use HasTenant;

    protected $table = 'case_payments';

    protected $fillable = [
        'case_id',
        'agency_id',
        'amount',
        'currency',
        'payment_method',
        'paid_at',
        'recorded_by',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'amount'   => 'integer',
        'paid_at'  => 'datetime',
        'metadata' => 'array',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'recorded_by');
    }
}
