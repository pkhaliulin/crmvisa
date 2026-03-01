<?php

namespace App\Modules\PublicPortal\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicLead extends Model
{
    use HasUuid;

    protected $table = 'public_leads';

    protected $fillable = [
        'public_user_id',
        'country_code',
        'visa_type',
        'score',
        'status',
        'assigned_agency_id',
        'notes',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(PublicUser::class, 'public_user_id');
    }
}
