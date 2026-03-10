<?php

namespace App\Modules\Case\Models;

use App\Modules\Document\Models\DocumentTemplate;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaCaseRule extends BaseModel
{
    protected $table = 'visa_case_rules';

    protected $fillable = [
        'country_code', 'visa_type', 'visa_subtype', 'applicant_type',
        'embassy_platform', 'submission_method',
        'appointment_required', 'biometrics_required', 'personal_visit_required', 'interview_possible',
        'processing_days_min', 'processing_days_max',
        'consular_fee_eur', 'service_fee_eur',
        'max_stay_days', 'validity_months',
        'workflow_steps', 'notes',
        'is_active', 'effective_from', 'effective_to',
    ];

    protected $casts = [
        'appointment_required'    => 'boolean',
        'biometrics_required'     => 'boolean',
        'personal_visit_required' => 'boolean',
        'interview_possible'      => 'boolean',
        'consular_fee_eur'        => 'decimal:2',
        'service_fee_eur'         => 'decimal:2',
        'workflow_steps'          => 'array',
        'is_active'               => 'boolean',
        'effective_from'          => 'date',
        'effective_to'            => 'date',
    ];

    // --- Relations ---

    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(VisaCaseRequiredDocument::class, 'visa_case_rule_id')->orderBy('display_order');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(VisaCaseCheckpoint::class, 'visa_case_rule_id')->orderBy('display_order');
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(VisaFormFieldMapping::class, 'visa_case_rule_id')->orderBy('step_number')->orderBy('display_order');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(VisaCase::class, 'visa_case_rule_id');
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Найти правило с fallback: точное -> без subtype -> без applicant_type.
     */
    public static function resolveRule(string $countryCode, string $visaType, ?string $subtype = null, ?string $applicantType = null): ?self
    {
        // 1. Точное совпадение
        $rule = static::active()
            ->where('country_code', $countryCode)
            ->where('visa_type', $visaType)
            ->where('visa_subtype', $subtype)
            ->where('applicant_type', $applicantType ?? 'adult')
            ->first();

        if ($rule) return $rule;

        // 2. Без applicant_type (default adult)
        if ($applicantType && $applicantType !== 'adult') {
            $rule = static::active()
                ->where('country_code', $countryCode)
                ->where('visa_type', $visaType)
                ->where('visa_subtype', $subtype)
                ->where('applicant_type', 'adult')
                ->first();

            if ($rule) return $rule;
        }

        // 3. Без subtype
        if ($subtype) {
            $rule = static::active()
                ->where('country_code', $countryCode)
                ->where('visa_type', $visaType)
                ->whereNull('visa_subtype')
                ->where('applicant_type', $applicantType ?? 'adult')
                ->first();

            if ($rule) return $rule;
        }

        // 4. Самый общий: без subtype + без applicant_type
        return static::active()
            ->where('country_code', $countryCode)
            ->where('visa_type', $visaType)
            ->whereNull('visa_subtype')
            ->where('applicant_type', 'adult')
            ->first();
    }
}
