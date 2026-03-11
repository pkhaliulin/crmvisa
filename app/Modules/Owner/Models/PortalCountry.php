<?php

namespace App\Modules\Owner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalCountry extends Model
{
    protected $table = 'portal_countries';
    protected $primaryKey = 'country_code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'country_code', 'name', 'name_uz', 'continent', 'visa_regime',
        'is_active', 'is_popular', 'is_high_approval', 'is_high_refusal',
        'sort_order', 'latitude', 'longitude', 'flag_emoji',
        'weight_finance', 'weight_ties', 'weight_travel', 'weight_profile',
        'weight_finances', 'weight_visa_history', 'weight_social_ties',
        'min_monthly_income_usd', 'min_score', 'commission_rate',
        'processing_days_standard', 'processing_days_expedited',
        'appointment_wait_days', 'buffer_days_recommended', 'destination_score_bonus',
        'visa_free_days', 'visa_on_arrival_days', 'evisa_available', 'evisa_processing_days',
        'invitation_required', 'hotel_booking_required', 'insurance_required',
        'bank_statement_required', 'return_ticket_required',
        'visa_fee_usd', 'evisa_fee_usd', 'avg_flight_cost_usd', 'avg_hotel_per_night_usd',
        'view_count', 'lead_count', 'case_count',
        'submission_types', 'appointment_required', 'personal_submission_required',
        'biometrics_required', 'photo_required',
        'has_visa_center', 'has_embassy',
        'min_balance_usd', 'finance_threshold',
        'visa_types', 'description', 'description_uz',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'weight_finance'          => 'float',
        'weight_ties'             => 'float',
        'weight_travel'           => 'float',
        'weight_profile'          => 'float',
        'weight_finances'         => 'float',
        'weight_visa_history'     => 'float',
        'weight_social_ties'      => 'float',
        'min_monthly_income_usd'  => 'integer',
        'min_score'               => 'integer',
        'sort_order'              => 'integer',
        'latitude'                => 'float',
        'longitude'               => 'float',
        'commission_rate'         => 'float',
        'processing_days_standard'  => 'integer',
        'processing_days_expedited' => 'integer',
        'appointment_wait_days'     => 'integer',
        'buffer_days_recommended'   => 'integer',
        'destination_score_bonus'   => 'integer',
        // Визовый режим
        'visa_free_days'            => 'integer',
        'visa_on_arrival_days'      => 'integer',
        'evisa_available'           => 'boolean',
        'evisa_processing_days'     => 'integer',
        // Требования
        'invitation_required'       => 'boolean',
        'hotel_booking_required'    => 'boolean',
        'insurance_required'        => 'boolean',
        'bank_statement_required'   => 'boolean',
        'return_ticket_required'    => 'boolean',
        // Стоимости
        'visa_fee_usd'              => 'float',
        'evisa_fee_usd'             => 'float',
        'avg_flight_cost_usd'       => 'integer',
        'avg_hotel_per_night_usd'   => 'integer',
        // Аналитика
        'view_count'                => 'integer',
        'lead_count'                => 'integer',
        'case_count'                => 'integer',
        // Флаги
        'is_popular'                => 'boolean',
        'is_high_approval'          => 'boolean',
        'is_high_refusal'           => 'boolean',
        // Подача
        'submission_types'              => 'array',
        'appointment_required'          => 'boolean',
        'personal_submission_required'  => 'boolean',
        'biometrics_required'           => 'boolean',
        'photo_required'                => 'boolean',
        // Визовый центр / Посольство
        'has_visa_center'               => 'boolean',
        'has_embassy'                   => 'boolean',
        // Финансы
        'min_balance_usd'               => 'integer',
        'finance_threshold'             => 'float',
    ];

    public function getVisaTypesAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    public function setVisaTypesAttribute($value)
    {
        $this->attributes['visa_types'] = is_array($value) ? json_encode($value) : $value;
    }

    public function visaTypeSettings(): HasMany
    {
        return $this->hasMany(CountryVisaTypeSetting::class, 'country_code', 'country_code');
    }

    public function countryRequirements(): HasMany
    {
        return $this->hasMany(\App\Modules\Document\Models\CountryVisaRequirement::class, 'country_code', 'country_code');
    }

    public function scoringWeights()
    {
        return $this->hasMany(\App\Modules\Scoring\Models\ScoringCountryWeight::class, 'country_code', 'country_code');
    }

    public function slaRules(): HasMany
    {
        return $this->hasMany(\App\Modules\Workflow\Models\SlaRule::class, 'country_code', 'country_code');
    }

    // Scopes

    public function scopeByRegime($query, string $regime)
    {
        return $query->where('visa_regime', $regime);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeByContinent($query, string $continent)
    {
        return $query->where('continent', $continent);
    }
}
