<?php

namespace App\Support\Abstracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    public function __construct(protected BaseRepository $repository) {}

    public function all(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns);
    }

    public function find(string|int $id): ?Model
    {
        return $this->repository->find($id);
    }

    public function findOrFail(string|int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    public function update(string|int $id, array $data): Model
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string|int $id): bool
    {
        return $this->repository->delete($id);
    }
}
