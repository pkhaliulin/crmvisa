<?php

namespace App\Modules\PublicPortal\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicScoreCache extends Model
{
    use HasUuid;

    protected $table = 'public_score_cache';

    protected $fillable = [
        'public_user_id',
        'country_code',
        'score',
        'breakdown',
        'recommendations',
        'calculated_at',
    ];

    protected $casts = [
        'score'           => 'integer',
        'breakdown'       => 'array',
        'recommendations' => 'array',
        'calculated_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(PublicUser::class, 'public_user_id');
    }
}
