<?php

namespace App\Modules\Finance\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseContract extends BaseModel
{
    use HasTenant;

    protected $table = 'case_contracts';

    protected $fillable = [
        'case_id',
        'agency_id',
        'contract_number',
        'version',
        'status',
        'total_price',
        'prepayment_amount',
        'remaining_amount',
        'currency',
        'payment_deadline',
        'full_payment_required_stage',
        'refund_policy',
        'terms_text',
        'client_confirmed_at',
        'client_confirmation_method',
        'signed_at',
        'locked_at',
        'pdf_path',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'version'              => 'integer',
        'total_price'          => 'integer',
        'prepayment_amount'    => 'integer',
        'remaining_amount'     => 'integer',
        'refund_policy'        => 'array',
        'metadata'             => 'array',
        'payment_deadline'     => 'date',
        'client_confirmed_at'  => 'datetime',
        'signed_at'            => 'datetime',
        'locked_at'            => 'datetime',
    ];

    // --- Relations ---

    public function case(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(CaseRefund::class, 'contract_id');
    }

    public function financialDocuments(): HasMany
    {
        return $this->hasMany(CaseFinancialDocument::class, 'contract_id');
    }

    // --- Business logic ---

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, ['draft', 'generated']);
    }

    public function lock(): void
    {
        if (!$this->isLocked()) {
            $this->update(['locked_at' => now()]);
        }
    }
}
