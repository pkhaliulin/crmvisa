<?php

namespace App\Modules\Case\Models;

use App\Modules\User\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseCheckpointStatus extends Model
{
    use HasUuid;

    protected $table = 'case_checkpoint_statuses';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected $fillable = [
        'case_id', 'checkpoint_id', 'is_completed', 'completed_at', 'completed_by', 'notes',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(VisaCaseCheckpoint::class, 'checkpoint_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
