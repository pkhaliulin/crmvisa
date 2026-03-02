<?php

namespace App\Modules\Client\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Client extends BaseModel
{
    use HasTenant, Notifiable, NormalizesPhone;

    protected $fillable = [
        'agency_id',
        'name',
        'email',
        'phone',
        'telegram_chat_id',
        'passport_number',
        'nationality',
        'date_of_birth',
        'passport_expires_at',
        'source',
        'notes',
    ];

    protected $casts = [
        'date_of_birth'       => 'date',
        'passport_expires_at' => 'date',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(\App\Modules\Case\Models\VisaCase::class, 'client_id');
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value !== null ? strtolower(trim($value)) : null;
    }

    public function isPassportExpiringSoon(int $days = 90): bool
    {
        if (! $this->passport_expires_at) {
            return false;
        }

        return $this->passport_expires_at->diffInDays(now(), false) >= -$days
            && $this->passport_expires_at->isFuture();
    }
}
