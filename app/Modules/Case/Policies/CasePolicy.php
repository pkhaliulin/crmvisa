<?php

namespace App\Modules\Case\Policies;

use App\Modules\Case\Models\VisaCase;
use App\Modules\User\Models\User;

class CasePolicy
{
    /**
     * Owner/superadmin видят все заявки агентства (RLS и так фильтрует по agency_id).
     * Manager видит только назначенные на него, если managers_see_all_cases = false.
     */
    public function view(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) {
            return false;
        }

        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        if ($user->role === 'manager') {
            return $user->agency?->managers_see_all_cases || $case->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Owner/superadmin: любую заявку агентства.
     * Manager: только назначенные на него.
     */
    public function update(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) {
            return false;
        }

        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        return $user->role === 'manager' && $case->assigned_to === $user->id;
    }

    /**
     * Перемещение по этапам: owner — все, manager — только свои.
     */
    public function moveStage(User $user, VisaCase $case): bool
    {
        return $this->update($user, $case);
    }

    /**
     * Переназначение менеджера: только owner/superadmin.
     */
    public function reassign(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) {
            return false;
        }

        return in_array($user->role, ['owner', 'superadmin']);
    }

    /**
     * Отмена заявки: только owner/superadmin.
     */
    public function cancel(User $user, VisaCase $case): bool
    {
        return $this->reassign($user, $case);
    }

    /**
     * Удаление: только owner/superadmin.
     */
    public function delete(User $user, VisaCase $case): bool
    {
        return $this->reassign($user, $case);
    }

    /**
     * Завершение заявки: owner — все, manager — свои.
     */
    public function complete(User $user, VisaCase $case): bool
    {
        return $this->update($user, $case);
    }
}
