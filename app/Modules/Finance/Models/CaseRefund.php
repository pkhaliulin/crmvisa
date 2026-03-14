<?php

namespace App\Modules\Finance\Models;

use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseRefund extends BaseModel
{
    use HasTenant;

    protected $table = 'case_refunds';

    protected $fillable = [
        'case_id',
        'agency_id',
        'contract_id',
        'payment_id',
        'amount',
        'currency',
        'reason',
        'type',
        'basis',
        'initiator',
        'status',
        'approved_by',
        'approved_at',
        'completed_at',
        'refund_method',
        'comment',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'amount'       => 'integer',
        'metadata'     => 'array',
        'approved_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CaseContract::class, 'contract_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CasePayment::class, 'payment_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
