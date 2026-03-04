<?php

namespace App\Modules\Group\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\PublicPortal\Models\PublicUser;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseGroup extends BaseModel
{
    protected $table = 'case_groups';

    protected $fillable = [
        'initiator_public_user_id',
        'name',
        'country_code',
        'visa_type',
        'agency_id',
        'payment_strategy',
        'status',
    ];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(PublicUser::class, 'initiator_public_user_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(CaseGroupMember::class, 'group_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(VisaCase::class, 'group_id');
    }
}
