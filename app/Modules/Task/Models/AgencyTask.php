<?php

namespace App\Modules\Task\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyTask extends BaseModel
{
    use HasTenant;

    /**
     * Статусы задачи (простой Agile-цикл):
     * new → accepted → completed → verified → closed
     * На любом этапе: → deferred, → cancelled
     */
    public const STATUSES = ['new', 'accepted', 'completed', 'verified', 'closed', 'deferred', 'cancelled'];
    public const ACTIVE_STATUSES = ['new', 'accepted', 'deferred'];
    public const TERMINAL_STATUSES = ['closed', 'cancelled'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const RECURRENCE_RULES = ['daily', 'weekdays', 'weekly', 'monthly', 'mon', 'tue', 'wed', 'thu', 'fri'];

    protected $fillable = [
        'agency_id', 'created_by', 'assigned_to', 'case_id',
        'title', 'description', 'priority', 'status',
        'due_date', 'completed_at', 'completed_by',
        'verified_by', 'verified_at',
        'recurrence_rule', 'recurrence_parent_id',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
        'verified_at'  => 'datetime',
    ];

    // -- Relations ──────────────────────────────────────────

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurrence_parent_id');
    }

    public function recurrenceChildren(): HasMany
    {
        return $this->hasMany(self::class, 'recurrence_parent_id');
    }

    // -- Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeOverdue($query)
    {
        return $query->active()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeDueToday($query)
    {
        return $query->active()
            ->where('due_date', now()->toDateString());
    }

    public function scopeByAssignee($query, string $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByCreator($query, string $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAwaitingVerification($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecurring($query)
    {
        return $query->whereNotNull('recurrence_rule')
            ->whereNull('recurrence_parent_id'); // только оригиналы
    }

    // -- Helpers ─────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_date
            && ! in_array($this->status, self::TERMINAL_STATUSES)
            && $this->status !== 'completed'
            && $this->status !== 'verified'
            && $this->due_date->isPast();
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_rule !== null && $this->recurrence_parent_id === null;
    }

    public function markCompleted(string $userId): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'completed_by' => $userId,
        ]);
    }

    public function markVerified(string $userId): void
    {
        $this->update([
            'status'      => 'verified',
            'verified_at' => now(),
            'verified_by' => $userId,
        ]);
    }

    /**
     * Создать следующую копию повторяющейся задачи.
     */
    public function createNextRecurrence(): ?self
    {
        if (! $this->isRecurring()) {
            return null;
        }

        $nextDue = $this->calculateNextDueDate();
        if (! $nextDue) {
            return null;
        }

        return self::create([
            'agency_id'            => $this->agency_id,
            'created_by'           => $this->created_by,
            'assigned_to'          => $this->assigned_to,
            'case_id'              => $this->case_id,
            'title'                => $this->title,
            'description'          => $this->description,
            'priority'             => $this->priority,
            'status'               => 'new',
            'due_date'             => $nextDue,
            'recurrence_parent_id' => $this->id,
        ]);
    }

    private function calculateNextDueDate(): ?\Carbon\Carbon
    {
        $base = $this->due_date ?? now();

        return match ($this->recurrence_rule) {
            'daily'    => $base->copy()->addDay(),
            'weekdays' => $this->nextWeekday($base),
            'weekly'   => $base->copy()->addWeek(),
            'monthly'  => $base->copy()->addMonth(),
            'mon'      => $base->copy()->next(\Carbon\Carbon::MONDAY),
            'tue'      => $base->copy()->next(\Carbon\Carbon::TUESDAY),
            'wed'      => $base->copy()->next(\Carbon\Carbon::WEDNESDAY),
            'thu'      => $base->copy()->next(\Carbon\Carbon::THURSDAY),
            'fri'      => $base->copy()->next(\Carbon\Carbon::FRIDAY),
            default    => null,
        };
    }

    private function nextWeekday(\Carbon\Carbon $date): \Carbon\Carbon
    {
        $next = $date->copy()->addDay();
        while ($next->isWeekend()) {
            $next->addDay();
        }
        return $next;
    }
}
