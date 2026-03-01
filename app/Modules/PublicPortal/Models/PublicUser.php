<?php

namespace App\Modules\PublicPortal\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class PublicUser extends Model
{
    use HasUuid;

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
        'had_visa_refusal'    => 'boolean',
        'had_overstay'        => 'boolean',
        'children_count'      => 'integer',
        'monthly_income_usd'  => 'integer',
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
        $fields = [
            'name', 'dob', 'citizenship', 'employment_type',
            'monthly_income_usd', 'marital_status',
        ];

        $filled  = collect($fields)->filter(fn ($f) => ! empty($this->$f))->count();
        $percent = (int) round($filled / count($fields) * 100);

        // +10 за каждый блок скоринга
        if ($this->has_property || $this->has_car)   $percent += 10;
        if ($this->has_schengen_visa || $this->has_us_visa) $percent += 10;
        if ($this->passport_number) $percent += 10;

        return min(100, $percent);
    }
}
