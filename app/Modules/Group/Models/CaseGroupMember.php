<?php

namespace App\Modules\Group\Models;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\PublicPortal\Models\PublicUser;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseGroupMember extends BaseModel
{
    protected $table = 'case_group_members';

    protected $fillable = [
        'group_id',
        'phone',
        'public_user_id',
        'case_id',
        'client_id',
        'name',
        'role',
        'status',
        'payment_covered',
    ];

    protected $casts = [
        'phone'           => 'encrypted',
        'payment_covered' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CaseGroup::class, 'group_id');
    }

    public function publicUser(): BelongsTo
    {
        return $this->belongsTo(PublicUser::class);
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
