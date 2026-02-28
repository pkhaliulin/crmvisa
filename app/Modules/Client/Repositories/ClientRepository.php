<?php

namespace App\Modules\Client\Repositories;

use App\Modules\Client\Models\Client;
use App\Support\Abstracts\BaseRepository;

class ClientRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Client());
    }

    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return Client::where(function ($q) use ($query) {
            $q->where('name', 'ilike', "%{$query}%")
              ->orWhere('email', 'ilike', "%{$query}%")
              ->orWhere('phone', 'ilike', "%{$query}%")
              ->orWhere('passport_number', 'ilike', "%{$query}%");
        })->get();
    }
}
