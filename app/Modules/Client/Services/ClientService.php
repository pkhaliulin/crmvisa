<?php

namespace App\Modules\Client\Services;

use App\Modules\Client\Events\ClientRegistered;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\ClientRepository;
use App\Support\Abstracts\BaseService;
use Illuminate\Database\Eloquent\Model;

class ClientService extends BaseService
{
    public function __construct(ClientRepository $repository)
    {
        parent::__construct($repository);
    }

    private function clientRepository(): ClientRepository
    {
        /** @var ClientRepository */
        return $this->repository;
    }

    public function create(array $data): Model
    {
        $client = parent::create($data);

        ClientRegistered::dispatch($client, $client->agency_id);

        return $client;
    }

    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return $this->clientRepository()->search($query);
    }
}
