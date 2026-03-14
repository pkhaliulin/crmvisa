<?php

namespace App\Modules\PublicPortal\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicUserDocument extends BaseModel
{
    protected $table = 'public_user_documents';

    // Не мультитенантная — нет agency_id
    public function getTenantColumn(): ?string
    {
        return null;
    }

    protected $fillable = [
        'public_user_id',
        'doc_type',
        'doc_number',
        'expires_at',
        'issue_date',
        'issued_by',
        'place_of_birth',
        'country',
        'first_name',
        'last_name',
        'middle_name',
        'script_type',
        'gender',
        'nationality',
        'dob',
        'pnfl',
        'mrz_line1',
        'mrz_line2',
        'ocr_provider',
        'ocr_confidence',
        'ocr_raw_data',
        'file_path',
        'is_current',
        'replaced_at',
        'replaced_reason',
    ];

    protected $casts = [
        'doc_number'      => 'encrypted',
        'expires_at'      => 'encrypted',
        'issue_date'      => 'encrypted',
        'issued_by'       => 'encrypted',
        'place_of_birth'  => 'encrypted',
        'first_name'      => 'encrypted',
        'last_name'       => 'encrypted',
        'middle_name'     => 'encrypted',
        'dob'             => 'encrypted',
        'pnfl'            => 'encrypted',
        'ocr_raw_data'    => 'encrypted:array',
        'ocr_confidence'  => 'float',
        'is_current'      => 'boolean',
        'replaced_at'     => 'datetime',
    ];

    protected $hidden = ['ocr_raw_data'];

    public function publicUser(): BelongsTo
    {
        return $this->belongsTo(PublicUser::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('doc_type', $type);
    }

    public function markReplaced(string $reason = 'new_document'): void
    {
        $this->update([
            'is_current'      => false,
            'replaced_at'     => now(),
            'replaced_reason' => $reason,
        ]);
    }
}
