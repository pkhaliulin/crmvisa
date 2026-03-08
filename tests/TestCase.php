<?php

namespace Tests;

use App\Modules\Agency\Models\Agency;
use App\Modules\Client\Models\Client;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Создать агентство + owner, аутентифицировать owner и вернуть [agency, owner].
     */
    protected function createAgencyWithOwner(array $agencyAttrs = [], array $ownerAttrs = []): array
    {
        $agency = Agency::factory()->create($agencyAttrs);
        $owner = User::factory()->owner()->create(array_merge(
            ['agency_id' => $agency->id],
            $ownerAttrs
        ));

        return [$agency, $owner];
    }

    /**
     * Создать менеджера для агентства.
     */
    protected function createManager(Agency $agency, array $attrs = []): User
    {
        return User::factory()->manager()->create(array_merge(
            ['agency_id' => $agency->id],
            $attrs
        ));
    }

    /**
     * Создать клиента для агентства.
     */
    protected function createClient(Agency $agency, array $attrs = []): Client
    {
        return Client::factory()->create(array_merge(
            ['agency_id' => $agency->id],
            $attrs
        ));
    }

    /**
     * Аутентифицировать пользователя и вернуть заголовки с JWT-токеном.
     */
    protected function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);
        return ['Authorization' => "Bearer {$token}"];
    }

    /**
     * Аутентифицировать пользователя (установить guard).
     */
    protected function actingAsUser(User $user): static
    {
        $this->actingAs($user, 'api');
        return $this;
    }
}
