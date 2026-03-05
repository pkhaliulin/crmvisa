<?php

namespace App\Modules\Client\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use App\Support\Traits\NormalizesName;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;

class Client extends BaseModel
{
    use HasTenant, Notifiable, NormalizesPhone, NormalizesName;

    protected $fillable = [
        'agency_id',
        'public_user_id',
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
        'phone'               => 'encrypted',
        'passport_number'     => 'encrypted',
        'date_of_birth'       => 'encrypted:date',
        'passport_expires_at' => 'encrypted:date',
    ];

    /**
     * Activity log: исключаем PII-поля из логирования.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['agency_id', 'name', 'email', 'nationality', 'source', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('clients');
    }

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
