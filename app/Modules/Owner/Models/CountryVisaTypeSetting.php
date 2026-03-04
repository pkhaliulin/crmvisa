<?php

namespace App\Modules\Owner\Models;

use App\Support\Abstracts\BaseModel;

class CountryVisaTypeSetting extends BaseModel
{
    protected $table = 'country_visa_type_settings';

    protected $fillable = [
        'country_code', 'visa_type',
        'preparation_days', 'appointment_wait_days',
        'processing_days_min', 'processing_days_max', 'processing_days_avg',
        'buffer_days', 'parallel_docs_allowed',
        'min_days_before_departure', 'recommended_days_before_departure',
        'avg_refusal_rate', 'biometrics_required', 'personal_visit_required', 'interview_required',
        'appointment_pattern', 'appointment_notes',
        'consular_fee_usd', 'service_fee_usd',
        'is_active', 'notes', 'description',
    ];

    protected $casts = [
        'preparation_days'                    => 'integer',
        'appointment_wait_days'               => 'integer',
        'processing_days_min'                 => 'integer',
        'processing_days_max'                 => 'integer',
        'processing_days_avg'                 => 'integer',
        'buffer_days'                         => 'integer',
        'parallel_docs_allowed'               => 'boolean',
        'min_days_before_departure'           => 'integer',
        'recommended_days_before_departure'   => 'integer',
        'avg_refusal_rate'                    => 'float',
        'biometrics_required'                 => 'boolean',
        'personal_visit_required'             => 'boolean',
        'interview_required'                  => 'boolean',
        'consular_fee_usd'                    => 'float',
        'service_fee_usd'                     => 'float',
        'is_active'                           => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->min_days_before_departure = $model->processing_days_avg
                + $model->appointment_wait_days
                + $model->buffer_days;

            $model->recommended_days_before_departure = $model->preparation_days
                + $model->appointment_wait_days
                + $model->processing_days_max
                + $model->buffer_days;
        });
    }

    public static function findSetting(string $countryCode, string $visaType): ?self
    {
        return static::where('country_code', strtoupper($countryCode))
            ->where('visa_type', $visaType)
            ->where('is_active', true)
            ->first();
    }

    public function country()
    {
        return $this->belongsTo(PortalCountry::class, 'country_code', 'country_code');
    }
}
