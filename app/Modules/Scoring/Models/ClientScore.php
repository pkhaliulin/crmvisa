<?php

namespace App\Modules\Scoring\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientScore extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_id', 'country_code', 'score',
        'block_scores', 'flags', 'recommendations',
        'is_blocked', 'calculated_at',
    ];

    protected $casts = [
        'score'           => 'decimal:2',
        'block_scores'    => 'array',
        'flags'           => 'array',
        'recommendations' => 'array',
        'is_blocked'      => 'boolean',
        'calculated_at'   => 'datetime',
    ];

    protected $appends = ['level', 'level_label'];

    public function getLevelAttribute(): string
    {
        return match (true) {
            $this->score >= 80 => 'high',
            $this->score >= 60 => 'medium',
            $this->score >= 40 => 'below_average',
            $this->score >= 20 => 'low',
            default            => 'very_low',
        };
    }

    public function getLevelLabelAttribute(): string
    {
        return match ($this->level) {
            'high'          => 'Высокие шансы одобрения',
            'medium'        => 'Хорошие шансы, усильте документы',
            'below_average' => 'Рекомендуем усилить профиль',
            'low'           => 'Высокий риск отказа',
            default         => 'Подача не рекомендуется',
        };
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Client\Models\Client::class);
    }
}
