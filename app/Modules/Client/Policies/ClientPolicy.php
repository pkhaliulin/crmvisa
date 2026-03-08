<?php

namespace App\Modules\Client\Policies;

use App\Modules\Client\Models\Client;
use App\Modules\User\Models\User;

class ClientPolicy
{
    /**
     * Просмотр: все роли видят клиентов своего агентства (RLS фильтрует).
     */
    public function view(User $user, Client $client): bool
    {
        return $client->agency_id === $user->agency_id;
    }

    /**
     * Обновление: owner — все, manager — только клиентов из своих кейсов.
     */
    public function update(User $user, Client $client): bool
    {
        if ($client->agency_id !== $user->agency_id) {
            return false;
        }

        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        if ($user->role === 'manager') {
            return $client->cases()->where('assigned_to', $user->id)->exists();
        }

        return false;
    }

    /**
     * Удаление: только owner/superadmin.
     */
    public function delete(User $user, Client $client): bool
    {
        if ($client->agency_id !== $user->agency_id) {
            return false;
        }

        return in_array($user->role, ['owner', 'superadmin']);
    }
}
