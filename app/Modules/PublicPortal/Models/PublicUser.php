<?php

namespace App\Modules\PublicPortal\Models;

use App\Modules\Group\Models\CaseGroup;
use App\Modules\Group\Models\CaseGroupMember;
use App\Support\Traits\HasUuid;
use App\Support\Traits\NormalizesName;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class PublicUser extends Model
{
    use HasUuid, NormalizesPhone, NormalizesName;

    protected $table = 'public_users';

    protected $fillable = [
        'phone',
        'recovery_email',
        'email_verified_at',
        'pin_hash',
        'api_token',
        'name',
        'first_name_lat',
        'last_name_lat',
        'middle_name_lat',
        'first_name_cyr',
        'last_name_cyr',
        'middle_name_cyr',
        'pnfl',
        'dob',
        'citizenship',
        'gender',
        'place_of_birth',
        'passport_number',
        'passport_expires_at',
        'passport_issue_date',
        'passport_issued_by',
        'passport_country',
        'passport_file_path',
        'passport_ocr_status',
        'passport_ocr_data',
        'id_doc_type',
        'id_doc_number',
        'id_doc_expires_at',
        'id_doc_issue_date',
        'id_doc_issued_by',
        'id_doc_ocr_status',
        'id_doc_ocr_data',
        'id_doc_file_path',
        'ocr_status',
        'ocr_raw_data',
        'employment_type',
        'monthly_income_usd',
        'marital_status',
        'has_children',
        'children_count',
        'has_property',
        'has_car',
        'has_schengen_visa',
        'has_us_visa',
        'had_visa_refusal',
        'had_overstay',
        'had_deportation',
        'visas_obtained_count',
        'refusals_count',
        'refusal_countries',
        'last_refusal_year',
        'employed_years',
        'education_level',
        'last_login_at',
    ];

    protected $hidden = ['pin_hash', 'api_token', 'ocr_raw_data', 'passport_ocr_data', 'id_doc_ocr_data'];

    protected $casts = [
        'dob'                 => 'encrypted',
        'passport_expires_at' => 'encrypted',
        'passport_number'     => 'encrypted',
        'passport_issue_date' => 'encrypted',
        'passport_issued_by'  => 'encrypted',
        'passport_ocr_data'   => 'encrypted:array',
        'ocr_raw_data'        => 'encrypted:array',
        'first_name_lat'      => 'encrypted',
        'last_name_lat'       => 'encrypted',
        'middle_name_lat'     => 'encrypted',
        'first_name_cyr'      => 'encrypted',
        'last_name_cyr'       => 'encrypted',
        'middle_name_cyr'     => 'encrypted',
        'pnfl'                => 'encrypted',
        'place_of_birth'      => 'encrypted',
        'id_doc_number'       => 'encrypted',
        'id_doc_expires_at'   => 'encrypted',
        'id_doc_issue_date'   => 'encrypted',
        'id_doc_issued_by'    => 'encrypted',
        'id_doc_ocr_data'     => 'encrypted:array',
        'recovery_email'      => 'encrypted',
        'last_login_at'       => 'datetime',
        'email_verified_at'   => 'datetime',
        'has_children'        => 'boolean',
        'has_property'        => 'boolean',
        'has_car'             => 'boolean',
        'has_schengen_visa'   => 'boolean',
        'has_us_visa'         => 'boolean',
        'had_visa_refusal'      => 'boolean',
        'had_overstay'          => 'boolean',
        'had_deportation'       => 'boolean',
        'children_count'        => 'integer',
        'monthly_income_usd'    => 'integer',
        'visas_obtained_count'  => 'integer',
        'refusals_count'        => 'integer',
        'last_refusal_year'     => 'integer',
        'employed_years'        => 'integer',
        'refusal_countries'     => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function scoreCache(): HasMany
    {
        return $this->hasMany(PublicScoreCache::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(PublicLead::class);
    }

    public function initiatedGroups(): HasMany
    {
        return $this->hasMany(CaseGroup::class, 'initiator_public_user_id');
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(CaseGroupMember::class, 'public_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PublicUserDocument::class);
    }

    public function currentForeignPassport(): ?PublicUserDocument
    {
        return $this->documents()->ofType('foreign_passport')->current()->latest()->first();
    }

    public function currentIdDocument(): ?PublicUserDocument
    {
        return $this->documents()->whereIn('doc_type', ['id_card', 'internal_passport'])->current()->latest()->first();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function setPin(string $pin): void
    {
        $this->update(['pin_hash' => Hash::make($pin)]);
    }

    public function verifyPin(string $pin): bool
    {
        return $this->pin_hash && Hash::check($pin, $this->pin_hash);
    }

    /** Количество заполненных полей профиля (0–100%) — каждое поле с индивидуальным весом */
    public function profileCompleteness(): int
    {
        // Каждое поле — индивидуальный вес, сумма = 100
        $weights = [
            ['w' => 12, 'filled' => ! empty($this->name)],
            ['w' => 10, 'filled' => ! empty($this->dob)],
            ['w' =>  9, 'filled' => ! empty($this->citizenship)],
            ['w' =>  5, 'filled' => ! empty($this->gender)],
            ['w' => 10, 'filled' => ! empty($this->passport_number)],
            ['w' =>  7, 'filled' => ! empty($this->passport_expires_at)],
            ['w' =>  9, 'filled' => ! empty($this->employment_type)],
            ['w' =>  5, 'filled' => $this->employed_years !== null],
            ['w' =>  5, 'filled' => ! empty($this->education_level)],
            ['w' => 10, 'filled' => $this->monthly_income_usd !== null && $this->monthly_income_usd > 0],
            ['w' => 10, 'filled' => ! empty($this->marital_status)],
            ['w' =>  8, 'filled' => $this->visas_obtained_count !== null],
        ];

        return collect($weights)->sum(fn ($f) => $f['filled'] ? $f['w'] : 0);
    }
}
