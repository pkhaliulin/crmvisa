<?php

namespace App\Modules\Case\Models;

use App\Modules\User\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStage extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'case_id',
        'user_id',
        'stage',
        'entered_at',
        'exited_at',
        'notes',
        'sla_due_at',
        'is_overdue',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'exited_at'  => 'datetime',
        'sla_due_at' => 'datetime',
        'is_overdue' => 'boolean',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
