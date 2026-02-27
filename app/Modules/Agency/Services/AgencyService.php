<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\DTOs\RegisterAgencyDTO;
use App\Modules\Agency\Enums\Plan;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Repositories\AgencyRepository;
use App\Support\Abstracts\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgencyService extends BaseService
{
    public function __construct(protected AgencyRepository $repository)
    {
        parent::__construct($repository);
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
    // Private helpers
    // -------------------------------------------------------------------------

    private function ensureEmailUnique(string $email): void
    {
        if ($this->repository->existsByEmail($email)) {
            throw ValidationException::withMessages([
                'email' => 'An agency with this email address already exists.',
            ]);
        }
    }

    private function resolveUniqueSlug(string $base): string
    {
        $slug      = $base;
        $increment = 1;

        while ($this->repository->existsBySlug($slug)) {
            $slug = $base . '-' . $increment++;
        }

        return $slug;
    }
}
