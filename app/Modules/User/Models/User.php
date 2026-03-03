<?php

namespace App\Modules\User\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Support\Traits\HasUuid;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements JWTSubject
{
    use HasUuid, SoftDeletes, Notifiable, NormalizesPhone, LogsActivity;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'agency_id',
        'name',
        'email',
        'phone',
        'telegram_username',
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

    public function cases(): HasMany
    {
        return $this->hasMany(VisaCase::class, 'assigned_to');
    }

    // -------------------------------------------------------------------------
    // Mutators
    // -------------------------------------------------------------------------

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value !== null ? strtolower(trim($value)) : null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'role', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('users');
    }
}
