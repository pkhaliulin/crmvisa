<?php

namespace App\Modules\Finance\Models;

use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseFinancialDocument extends BaseModel
{
    use HasTenant;

    protected $table = 'case_financial_documents';

    protected $fillable = [
        'case_id',
        'agency_id',
        'contract_id',
        'type',
        'document_number',
        'date',
        'status',
        'pdf_path',
        'version',
        'amount',
        'currency',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'date'     => 'date',
        'version'  => 'integer',
        'amount'   => 'integer',
        'metadata' => 'array',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CaseContract::class, 'contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
