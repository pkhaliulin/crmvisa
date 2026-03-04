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

    /** Количество заполненных полей профиля (0–100%) */
    public function profileCompleteness(): int
    {
        // Обязательные поля — 60 баллов (по 10 каждое)
        $required = ['name', 'dob', 'citizenship', 'employment_type', 'monthly_income_usd', 'marital_status'];
        $filled   = collect($required)->filter(fn ($f) => ! empty($this->$f))->count();
        $percent  = $filled * 10; // max 60

        // Занятость — стаж
        if ($this->employed_years !== null && $this->employed_years >= 0) $percent += 5;

        // Семья
        if ($this->has_property || $this->has_car) $percent += 5;

        // Визовая история — явно указана (даже если нет виз = уже заполнено)
        if ($this->visas_obtained_count !== null) $percent += 5;
        if ($this->refusals_count !== null)       $percent += 5;

        // Сильные визы — бонус
        if ($this->has_schengen_visa || $this->has_us_visa) $percent += 10;

        // Данные паспорта
        if ($this->passport_number) $percent += 10;

        return min(100, $percent);
    }
}
