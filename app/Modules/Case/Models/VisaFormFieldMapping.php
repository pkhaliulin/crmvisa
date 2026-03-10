<?php

namespace App\Modules\Case\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaFormFieldMapping extends BaseModel
{
    protected $table = 'visa_form_field_mappings';

    protected $fillable = [
        'visa_case_rule_id', 'step_number', 'step_title',
        'field_key', 'field_label', 'field_type', 'options',
        'default_value', 'mapping_source', 'transform_rule',
        'help_text', 'validation_rules', 'is_required',
        'display_order', 'is_active',
    ];

    protected $casts = [
        'options'          => 'array',
        'validation_rules' => 'array',
        'is_required'      => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(VisaCaseRule::class, 'visa_case_rule_id');
    }

    public function scopeForStep(Builder $query, int $step): Builder
    {
        return $query->where('step_number', $step)->orderBy('display_order');
    }
}
