<?php

namespace App\Modules\Document\Policies;

use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;

class DocumentPolicy
{
    /**
     * Просмотр документа: все роли внутри агентства.
     */
    public function view(User $user, Document $document): bool
    {
        return $document->agency_id === $user->agency_id;
    }

    /**
     * Загрузка документа: owner — все кейсы, manager — свои кейсы.
     */
    public function upload(User $user, Document $document): bool
    {
        if ($document->agency_id !== $user->agency_id) {
            return false;
        }

        if (in_array($user->role, ['owner', 'superadmin'])) {
            return true;
        }

        if ($user->role === 'manager') {
            return $document->case && $document->case->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Изменение статуса документа (approve/reject): owner — все, manager — свои кейсы.
     */
    public function updateStatus(User $user, Document $document): bool
    {
        return $this->upload($user, $document);
    }

    /**
     * Удаление: owner/superadmin.
     */
    public function delete(User $user, Document $document): bool
    {
        if ($document->agency_id !== $user->agency_id) {
            return false;
        }

        return in_array($user->role, ['owner', 'superadmin']);
    }
}
