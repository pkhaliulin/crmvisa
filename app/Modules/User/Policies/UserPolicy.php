<?php

namespace App\Modules\User\Policies;

use App\Modules\User\Models\User;

class UserPolicy
{
    /**
     * Просмотр списка пользователей: owner/superadmin.
     */
    public function viewAny(User $authUser): bool
    {
        return in_array($authUser->role, ['owner', 'superadmin']);
    }

    /**
     * Просмотр конкретного пользователя: owner/superadmin своего агентства.
     */
    public function view(User $authUser, User $targetUser): bool
    {
        if ($authUser->agency_id !== $targetUser->agency_id) {
            return false;
        }

        return in_array($authUser->role, ['owner', 'superadmin']);
    }

    /**
     * Создание пользователя: только owner/superadmin.
     */
    public function create(User $authUser): bool
    {
        return in_array($authUser->role, ['owner', 'superadmin']);
    }

    /**
     * Обновление: owner/superadmin, только для своего агентства.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        return $this->view($authUser, $targetUser);
    }

    /**
     * Удаление: owner/superadmin, нельзя удалить себя или другого owner.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->agency_id !== $targetUser->agency_id) {
            return false;
        }

        if (!in_array($authUser->role, ['owner', 'superadmin'])) {
            return false;
        }

        if ($authUser->id === $targetUser->id) {
            return false;
        }

        if ($targetUser->role === 'owner') {
            return false;
        }

        return true;
    }
}
