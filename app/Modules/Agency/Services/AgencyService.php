<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\DTOs\RegisterAgencyDTO;
use App\Modules\Agency\Enums\Plan;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Repositories\AgencyRepository;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Payment\Models\MarketplaceLead;
use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgencyService extends BaseService
{
    public function __construct(AgencyRepository $repository)
    {
        parent::__construct($repository);
    }

    private function agencyRepository(): AgencyRepository
    {
        /** @var AgencyRepository */
        return $this->repository;
    }

    public function register(RegisterAgencyDTO $dto): Agency
    {
        $this->ensureEmailUnique($dto->email);

        $slug = $this->resolveUniqueSlug($dto->slug);

        /** @var Agency */
        return $this->repository->create([
            ...$dto->toArray(),
            'slug'            => $slug,
            'plan'            => Plan::Trial->value,
            'plan_expires_at' => Carbon::now()->addDays(Plan::Trial->trialDays()),
            'is_active'       => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Авто-распределение лидов
    // -------------------------------------------------------------------------

    /**
     * Назначить лид менеджеру по стратегии агентства.
     * Возвращает User или null (если manual или нет менеджеров).
     */
    public function assignLead(MarketplaceLead $lead, Agency $agency): ?User
    {
        return match ($agency->lead_assignment_mode) {
            'round_robin'  => $this->assignRoundRobin($agency),
            'by_workload'  => $this->assignByWorkload($agency),
            'by_country'   => $this->assignByCountry($agency, $lead->country_code),
            default        => null, // manual
        };
    }

    private function managers(Agency $agency)
    {
        return User::where('agency_id', $agency->id)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();
    }

    private function assignRoundRobin(Agency $agency): ?User
    {
        $managers = $this->managers($agency);
        if ($managers->isEmpty()) {
            return null;
        }

        $cacheKey = "agency:{$agency->id}:last_assigned_manager";
        $lastId   = Cache::get($cacheKey);

        $ids   = $managers->pluck('id')->toArray();
        $index = array_search($lastId, $ids, true);
        $next  = $index === false ? 0 : ($index + 1) % count($ids);

        $manager = $managers->get($next);
        Cache::put($cacheKey, $manager->id, now()->addDays(30));

        return $manager;
    }

    private function assignByWorkload(Agency $agency): ?User
    {
        return User::where('agency_id', $agency->id)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->withCount(['cases as active_cases' => fn ($q) => $q->whereNotIn('stage', ['result'])])
            ->orderBy('active_cases')
            ->first();
    }

    private function assignByCountry(Agency $agency, ?string $countryCode): ?User
    {
        if (! $countryCode) {
            return $this->assignByWorkload($agency);
        }

        $manager = User::where('agency_id', $agency->id)
            ->where('role', 'manager')
            ->where('is_active', true)
            ->withCount(['cases as country_cases' => fn ($q) => $q
                ->where('country_code', $countryCode)
                ->where('stage', 'result')
            ])
            ->orderByDesc('country_cases')
            ->first();

        return $manager && $manager->country_cases > 0 ? $manager : $this->assignByWorkload($agency);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function ensureEmailUnique(string $email): void
    {
        if ($this->agencyRepository()->existsByEmail($email)) {
            throw ValidationException::withMessages([
                'email' => 'An agency with this email address already exists.',
            ]);
        }
    }

    private function resolveUniqueSlug(string $base): string
    {
        $slug      = $base;
        $increment = 1;

        while ($this->agencyRepository()->existsBySlug($slug)) {
            $slug = $base . '-' . $increment++;
        }

        return $slug;
    }
}
