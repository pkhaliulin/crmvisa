<?php

namespace App\Modules\Case\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaCaseCheckpoint extends BaseModel
{
    protected $table = 'visa_case_checkpoints';

    protected $fillable = [
        'visa_case_rule_id', 'stage', 'slug', 'title', 'description',
        'check_type', 'auto_check_config', 'is_blocking', 'display_order', 'is_active',
    ];

    protected $casts = [
        'auto_check_config' => 'array',
        'is_blocking'       => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(VisaCaseRule::class, 'visa_case_rule_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(CaseCheckpointStatus::class, 'checkpoint_id');
    }
}
