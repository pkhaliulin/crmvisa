<?php

namespace App\Modules\Case\Models;

use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseActivity extends BaseModel
{
    protected $table = 'case_activities';

    protected $fillable = [
        'case_id',
        'user_id',
        'type',
        'description',
        'metadata',
        'is_internal',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'is_internal' => 'boolean',
    ];

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
