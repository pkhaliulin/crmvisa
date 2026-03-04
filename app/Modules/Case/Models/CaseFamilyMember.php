<?php

namespace App\Modules\Case\Models;

use App\Modules\PublicPortal\Models\PublicUserFamilyMember;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseFamilyMember extends Model
{
    use HasUuids;

    protected $table = 'case_family_members';

    protected $fillable = [
        'case_id',
        'family_member_id',
    ];

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(PublicUserFamilyMember::class, 'family_member_id');
    }
}
