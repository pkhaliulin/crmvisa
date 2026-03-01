<?php

namespace App\Modules\Agency\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyReview extends Model
{
    use HasUuid;

    protected $table = 'agency_reviews';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'agency_id',
        'public_user_id',
        'client_name',
        'rating',
        'comment',
        'is_published',
    ];

    protected $casts = [
        'rating'       => 'integer',
        'is_published' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
