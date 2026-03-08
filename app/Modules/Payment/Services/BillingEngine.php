<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\AgencyWallet;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\PaymentTransaction;

/**
 * Фасад биллинга — делегирует вызовы подсервисам.
 * Все публичные методы сохранены с оригинальными сигнатурами.
 */
class BillingEngine
{
    public function __construct(
        private readonly SubscriptionService       $subscriptionService,
        private readonly PlanChangeService         $planChangeService,
        private readonly PaymentProcessingService  $paymentProcessingService,
        private readonly BillingHelperService      $helperService,
    ) {}

    // =========================================================================
    // Подписки
    // =========================================================================

    public function subscribe(
        Agency  $agency,
        string  $planSlug,
        string  $billingPeriod = 'monthly',
        string  $paymentModel = 'prepaid',
        ?string $couponCode = null,
        ?string $actorId = null,
    ): AgencySubscription {
        return $this->subscriptionService->subscribe($agency, $planSlug, $billingPeriod, $paymentModel, $couponCode, $actorId);
    }

    public function cancelSubscription(Agency $agency, ?string $actorId = null): bool
    {
        return $this->subscriptionService->cancelSubscription($agency, $actorId);
    }

    public function renewSubscription(AgencySubscription $subscription): bool
    {
        return $this->subscriptionService->renewSubscription($subscription);
    }

    public function activeSubscription(Agency $agency): ?AgencySubscription
    {
        return $this->subscriptionService->activeSubscription($agency);
    }

    // =========================================================================
    // Смена тарифа
    // =========================================================================

    public function changePlan(
        Agency  $agency,
        string  $newPlanSlug,
        string  $billingPeriod = 'monthly',
        ?string $actorId = null,
    ): array {
        return $this->planChangeService->changePlan($agency, $newPlanSlug, $billingPeriod, $actorId);
    }

    public function classifyPlanChange(
        AgencySubscription $current,
        BillingPlan $newPlan,
        string $newPeriod,
    ): string {
        return $this->planChangeService->classifyPlanChange($current, $newPlan, $newPeriod);
    }

    public function calculateProRataCredit(AgencySubscription $subscription): int
    {
        return $this->planChangeService->calculateProRataCredit($subscription);
    }

    public function checkDowngradeLimits(Agency $agency, BillingPlan $newPlan): array
    {
        return $this->planChangeService->checkDowngradeLimits($agency, $newPlan);
    }

    public function applyPendingDowngrades(): int
    {
        return $this->planChangeService->applyPendingDowngrades();
    }

    public function cancelPendingDowngrade(Agency $agency, ?string $actorId = null): bool
    {
        return $this->planChangeService->cancelPendingDowngrade($agency, $actorId);
    }

    // =========================================================================
    // Платежи
    // =========================================================================

    public function payActivationFee(
        Agency  $agency,
        string  $provider = 'manual',
        ?string $providerTxId = null,
    ): PaymentTransaction {
        return $this->paymentProcessingService->payActivationFee($agency, $provider, $providerTxId);
    }

    public function paySubscription(
        Agency  $agency,
        string  $provider = 'manual',
        ?string $providerTxId = null,
    ): PaymentTransaction {
        return $this->paymentProcessingService->paySubscription($agency, $provider, $providerTxId);
    }

    public function creditAgencyEarnings(Agency $agency, int $amount, string $caseId, string $description = ''): void
    {
        $this->paymentProcessingService->creditAgencyEarnings($agency, $amount, $caseId, $description);
    }

    public function deductEarnFirst(Agency $agency, int $orderAmount, string $orderId): ?PaymentTransaction
    {
        return $this->paymentProcessingService->deductEarnFirst($agency, $orderAmount, $orderId);
    }

    // =========================================================================
    // Вспомогательные
    // =========================================================================

    public function calculateVat(int $amount): array
    {
        return $this->helperService->calculateVat($amount);
    }

    public function getWallet(Agency $agency): AgencyWallet
    {
        return $this->helperService->getWallet($agency);
    }

    public function checkLimits(Agency $agency): array
    {
        return $this->helperService->checkLimits($agency);
    }

    public function processExpiredSubscriptions(): int
    {
        return $this->helperService->processExpiredSubscriptions();
    }
}
