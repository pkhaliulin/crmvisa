<?php

namespace App\Modules\PublicPortal\Models;

use App\Modules\Group\Models\CaseGroup;
use App\Modules\Group\Models\CaseGroupMember;
use App\Support\Traits\HasUuid;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class PublicUser extends Model
{
    use HasUuid, NormalizesPhone;

    protected $table = 'public_users';

    protected $fillable = [
        'phone',
        'pin_hash',
        'api_token',
        'name',
        'dob',
        'citizenship',
        'gender',
        'passport_number',
        'passport_expires_at',
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
        'last_login_at',
    ];

    protected $hidden = ['pin_hash', 'api_token'];

    protected $casts = [
        'dob'                 => 'date',
        'passport_expires_at' => 'date',
        'last_login_at'       => 'datetime',
        'ocr_raw_data'        => 'array',
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
            ['w' => 10, 'filled' => ! empty($this->citizenship)],
            ['w' =>  6, 'filled' => ! empty($this->gender)],
            ['w' => 10, 'filled' => ! empty($this->passport_number)],
            ['w' =>  7, 'filled' => ! empty($this->passport_expires_at)],
            ['w' => 10, 'filled' => ! empty($this->employment_type)],
            ['w' =>  6, 'filled' => $this->employed_years !== null],
            ['w' => 10, 'filled' => $this->monthly_income_usd !== null && $this->monthly_income_usd > 0],
            ['w' => 10, 'filled' => ! empty($this->marital_status)],
            ['w' =>  9, 'filled' => $this->visas_obtained_count !== null],
        ];

        return collect($weights)->sum(fn ($f) => $f['filled'] ? $f['w'] : 0);
    }
}
