<?php

namespace App\Modules\Task\Policies;

use App\Modules\Task\Models\AgencyTask;
use App\Modules\User\Models\User;

class TaskPolicy
{
    /**
     * Owner/superadmin видят все задачи агентства.
     * Manager видит только назначенные на него или созданные им.
     */
    public function view(User $user, AgencyTask $task): bool
    {
        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        return $task->assigned_to === $user->id || $task->created_by === $user->id;
    }

    /**
     * Создавать задачи могут все (owner, manager).
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'superadmin', 'manager']);
    }

    /**
     * Owner/superadmin: любую задачу агентства.
     * Manager: только созданные им.
     * Назначенную руководителем задачу менеджер НЕ может редактировать.
     */
    public function update(User $user, AgencyTask $task): bool
    {
        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        return $task->created_by === $user->id;
    }

    /**
     * Удаление: owner/superadmin — любую, manager — только свои.
     */
    public function delete(User $user, AgencyTask $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Переход статуса (transition): может выполнять задачу любой,
     * кому она назначена или кто её создал.
     * Owner/superadmin — любую.
     */
    public function transition(User $user, AgencyTask $task): bool
    {
        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        return $task->assigned_to === $user->id || $task->created_by === $user->id;
    }

    /**
     * Defer/reopen: owner — любую, manager — назначенные на него или созданные им.
     * Менеджер может отложить задачу руководителя, чтобы тот видел что задача не проигнорирована.
     */
    public function setStatus(User $user, AgencyTask $task): bool
    {
        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        return $task->assigned_to === $user->id || $task->created_by === $user->id;
    }
}
