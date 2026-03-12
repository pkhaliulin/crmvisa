<?php

namespace App\Modules\Case\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\Client\Models\Client;
use App\Modules\Group\Models\CaseGroup;
use App\Modules\Owner\Models\CountryVisaTypeSetting;
use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Database\Factories\Modules\Case\VisaCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisaCase extends BaseModel
{
    use HasTenant, HasFactory;

    protected static function newFactory(): VisaCaseFactory
    {
        return VisaCaseFactory::new();
    }


    protected $table = 'cases';

    protected static function booted(): void
    {
        static::creating(function (self $case) {
            if (! $case->case_number) {
                $case->case_number = static::generateCaseNumber();
            }
            // Авто-расчет дедлайна при создании
            if ($case->travel_date && ! $case->critical_date) {
                $case->critical_date = static::calcCriticalDate(
                    $case->country_code, $case->visa_type, $case->travel_date
                );
            }
        });

        static::updating(function (self $case) {
            // Пересчет дедлайна при изменении travel_date (если critical_date не менялась вручную)
            if ($case->isDirty('travel_date') && ! $case->isDirty('critical_date') && $case->travel_date) {
                $case->critical_date = static::calcCriticalDate(
                    $case->country_code, $case->visa_type, $case->travel_date
                );
            }
        });
    }

    /**
     * Рассчитать крайний срок подачи документов.
     * Формула: travel_date - min_days_before_departure (processing_avg + appointment_wait + buffer)
     */
    public static function calcCriticalDate(string $countryCode, string $visaType, $travelDate): ?Carbon
    {
        $date = $travelDate instanceof Carbon ? $travelDate : Carbon::parse($travelDate);

        $setting = CountryVisaTypeSetting::findSetting($countryCode, $visaType);
        if (! $setting || ! $setting->min_days_before_departure) {
            // Fallback: 30 дней до вылета если нет справочных данных по стране
            return $date->copy()->subDays(30);
        }

        return $date->copy()->subDays($setting->min_days_before_departure);
    }

    /**
     * Пояснение расчета дедлайна для UI.
     */
    public static function deadlineExplanation(string $countryCode, string $visaType): ?array
    {
        $setting = CountryVisaTypeSetting::findSetting($countryCode, $visaType);
        if (! $setting) {
            return null;
        }
        return [
            'processing_days'           => $setting->processing_days_avg,
            'appointment_wait_days'     => $setting->appointment_wait_days,
            'buffer_days'               => $setting->buffer_days,
            'min_days_before_departure' => $setting->min_days_before_departure,
        ];
    }

    public static function generateCaseNumber(): string
    {
        do {
            $number = 'VB-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (DB::table('cases')->where('case_number', $number)->exists());

        return $number;
    }

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
        'return_date',
        'appointment_date',
        'appointment_time',
        'appointment_location',
        'notes',
        'submitted_at',
        'expected_result_date',
        'result_type',
        'result_notes',
        'reviewed_at',
        'visa_issued_at',
        'visa_received_at',
        'visa_validity',
        'rejection_reason',
        'can_reapply',
        'reapply_recommendation',
        'previous_case_id',
        'last_manager_update_at',
        'lead_source',
        'lead_channel_code',
        'lock_version',
        'visa_case_rule_id',
        'visa_subtype',
        'applicant_type',
        'embassy_platform',
        'submission_method',
        'appointment_required',
        'biometrics_required',
        'reference_number',
        'readiness_score',
        'form_data',
        'missing_items',
        'next_action',
    ];

    protected $casts = [
        'critical_date'        => 'date',
        'travel_date'          => 'date',
        'return_date'          => 'date',
        'appointment_date'     => 'date',
        'submitted_at'         => 'datetime',
        'expected_result_date' => 'date',
        'visa_issued_at'       => 'date',
        'visa_received_at'     => 'date',
        'reviewed_at'          => 'datetime',
        'can_reapply'            => 'boolean',
        'last_manager_update_at' => 'datetime',
        'appointment_required'   => 'boolean',
        'biometrics_required'    => 'boolean',
        'readiness_score'        => 'integer',
        'form_data'              => 'array',
        'missing_items'          => 'array',
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

    public function rule(): BelongsTo
    {
        return $this->belongsTo(VisaCaseRule::class, 'visa_case_rule_id');
    }

    public function checkpointStatuses(): HasMany
    {
        return $this->hasMany(CaseCheckpointStatus::class, 'case_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CaseActivity::class, 'case_id')->orderByDesc('created_at');
    }

    /**
     * Оптимистичное обновление: проверяет lock_version перед записью.
     * Бросает ConflictException если версия устарела (другой менеджер обновил раньше).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function optimisticUpdate(array $data, ?int $expectedVersion = null): bool
    {
        $version = $expectedVersion ?? ($data['lock_version'] ?? $this->lock_version);
        unset($data['lock_version']);

        $affected = static::where('id', $this->id)
            ->where('lock_version', $version)
            ->update(array_merge($data, [
                'lock_version' => \Illuminate\Support\Facades\DB::raw('lock_version + 1'),
                'updated_at'   => now(),
            ]));

        if ($affected === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lock_version' => ['Заявка была изменена другим пользователем. Обновите страницу и попробуйте снова.'],
            ]);
        }

        $this->refresh();
        return true;
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
