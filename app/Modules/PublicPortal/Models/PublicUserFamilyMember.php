<?php

namespace App\Modules\PublicPortal\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicUserFamilyMember extends Model
{
    use HasUuids;

    protected $table = 'public_user_family_members';

    protected $fillable = [
        'public_user_id',
        'name',
        'relationship',
        'dob',
        'gender',
        'citizenship',
        'passport_number',
        'passport_expires_at',
    ];

    protected $casts = [
        'dob'                => 'encrypted:date',
        'passport_expires_at' => 'encrypted:date',
        'passport_number'     => 'encrypted',
    ];

    public function publicUser(): BelongsTo
    {
        return $this->belongsTo(PublicUser::class, 'public_user_id');
    }

    public function isMinor(): bool
    {
        return $this->dob && $this->dob->age < 18;
    }
}
