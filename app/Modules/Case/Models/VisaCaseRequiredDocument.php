<?php

namespace App\Modules\Case\Models;

use App\Modules\Document\Models\DocumentTemplate;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaCaseRequiredDocument extends BaseModel
{
    protected $table = 'visa_case_required_documents';

    protected $fillable = [
        'visa_case_rule_id', 'document_template_id',
        'name', 'description', 'requirement_level', 'condition_description',
        'applicant_types', 'accepted_formats', 'requires_translation',
        'min_validity_rule', 'notes', 'display_order', 'is_active',
    ];

    protected $casts = [
        'applicant_types'      => 'array',
        'requires_translation' => 'boolean',
        'is_active'            => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(VisaCaseRule::class, 'visa_case_rule_id');
    }

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }
}
