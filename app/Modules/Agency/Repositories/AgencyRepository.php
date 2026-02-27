<?php

namespace App\Modules\Agency\Repositories;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseRepository;

class AgencyRepository extends BaseRepository
{
    public function __construct(Agency $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Agency
    {
        /** @var Agency|null */
        return $this->model->where('slug', $slug)->first();
    }

    public function findByEmail(string $email): ?Agency
    {
        /** @var Agency|null */
        return $this->model->where('email', $email)->first();
    }

    public function existsBySlug(string $slug): bool
    {
        return $this->model->where('slug', $slug)->exists();
    }

    public function existsByEmail(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }
}
