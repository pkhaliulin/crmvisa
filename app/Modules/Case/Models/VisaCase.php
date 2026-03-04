<?php

namespace App\Modules\Case\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\Client\Models\Client;
use App\Modules\Group\Models\CaseGroup;
use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaCase extends BaseModel
{
    use HasTenant;

    protected $table = 'cases';

    protected $fillable = [
        'agency_id',
        'group_id',
        'client_id',
        'assigned_to',
        'country_code',
        'visa_type',
        'stage',
        'public_status',
        'payment_status',
        'priority',
        'critical_date',
        'travel_date',
        'appointment_date',
        'appointment_time',
        'appointment_location',
        'notes',
    ];

    protected $casts = [
        'critical_date'    => 'date',
        'travel_date'      => 'date',
        'appointment_date' => 'date',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CaseGroup::class, 'group_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(CaseStage::class, 'case_id')->orderBy('entered_at');
    }

    // Критично: дедлайн через ≤2 дня или уже прошёл
    public function isCritical(): bool
    {
        if (! $this->critical_date || $this->stage === 'result') {
            return false;
        }

        $daysLeft = now()->diffInDays($this->critical_date, false);

        return $daysLeft <= 2;
    }

    // Предупреждение: дедлайн через 3–5 дней
    public function isWarning(): bool
    {
        if (! $this->critical_date || $this->stage === 'result') {
            return false;
        }

        $daysLeft = now()->diffInDays($this->critical_date, false);

        return $daysLeft > 2 && $daysLeft <= 5;
    }
}
