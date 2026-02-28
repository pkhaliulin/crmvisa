<?php

namespace App\Modules\User\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasUuid, SoftDeletes, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'agency_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'email_verified_at'  => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // JWT
    // -------------------------------------------------------------------------

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'agency_id' => $this->agency_id,
            'role'      => $this->role,
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
