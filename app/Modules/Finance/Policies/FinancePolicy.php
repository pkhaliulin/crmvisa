<?php

namespace App\Modules\Finance\Policies;

use App\Modules\Case\Models\VisaCase;
use App\Modules\User\Models\User;

class FinancePolicy
{
    /**
     * Просмотр финансов заявки — все роли внутри агентства.
     */
    public function viewPayments(User $user, VisaCase $case): bool
    {
        return $case->agency_id === $user->agency_id;
    }

    /**
     * Записать платёж — owner и менеджер (только свои заявки).
     */
    public function recordPayment(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) return false;
        if (in_array($user->role, ['owner', 'superadmin'])) return true;
        return $user->role === 'manager' && $case->assigned_to === $user->id;
    }

    /**
     * Удалить платёж — только owner/superadmin.
     */
    public function deletePayment(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) return false;
        return in_array($user->role, ['owner', 'superadmin']);
    }

    /**
     * Изменить коммерческие условия (стоимость, дедлайн) — owner и менеджер.
     */
    public function updatePaymentSettings(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) return false;
        if (in_array($user->role, ['owner', 'superadmin'])) return true;
        return $user->role === 'manager' && $case->assigned_to === $user->id;
    }

    /**
     * Создать/подписать договор — owner и менеджер.
     */
    public function manageContract(User $user, VisaCase $case): bool
    {
        return $this->recordPayment($user, $case);
    }

    /**
     * Создать возврат — owner и менеджер (инициация).
     */
    public function createRefund(User $user, VisaCase $case): bool
    {
        return $this->recordPayment($user, $case);
    }

    /**
     * Утвердить возврат — только owner/superadmin.
     */
    public function approveRefund(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) return false;
        return in_array($user->role, ['owner', 'superadmin']);
    }

    /**
     * Изменить подписанный договор — только owner + новая версия.
     */
    public function modifySignedContract(User $user, VisaCase $case): bool
    {
        if ($case->agency_id !== $user->agency_id) return false;
        return in_array($user->role, ['owner', 'superadmin']);
    }

    /**
     * Доступ к финансовому кабинету — только owner/superadmin.
     */
    public function viewFinanceDashboard(User $user): bool
    {
        return in_array($user->role, ['owner', 'superadmin']);
    }
}
