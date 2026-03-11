<?php

namespace Tests\Feature\Billing;

use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\AgencyWallet;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\Coupon;
use App\Modules\Payment\Models\LedgerEntry;
use App\Modules\Payment\Models\PlatformSetting;
use App\Modules\Payment\Services\BillingEngine;
use App\Modules\Payment\Services\PlanChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private BillingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(BillingEngine::class);
    }

    private function createPlan(array $attrs = []): BillingPlan
    {
        return BillingPlan::create(array_merge([
            'slug'                     => 'pro',
            'name'                     => 'Pro',
            'price_monthly'            => 1900,
            'price_yearly'             => 19000,
            'price_uzs'                => 200000,
            'activation_fee_uzs'       => 0,
            'earn_first_enabled'       => false,
            'earn_first_deduction_pct' => 0,
            'max_managers'             => 5,
            'max_cases'                => 100,
            'max_leads_per_month'      => 0,
            'has_marketplace'          => true,
            'has_priority_support'     => false,
            'has_api_access'           => false,
            'has_custom_domain'        => false,
            'has_white_label'          => false,
            'has_analytics'            => false,
            'trial_days'               => 0,
            'grace_period_days'        => 3,
            'is_active'                => true,
            'is_public'                => true,
            'is_recommended'           => false,
            'features'                 => [],
            'sort_order'               => 0,
        ], $attrs));
    }

    private function seedPlatformSettings(): void
    {
        PlatformSetting::updateOrCreate(
            ['key' => 'vat_enabled'],
            ['value' => 'false', 'type' => 'boolean', 'group' => 'billing']
        );
        PlatformSetting::updateOrCreate(
            ['key' => 'vat_rate'],
            ['value' => '12.00', 'type' => 'decimal', 'group' => 'billing']
        );
        PlatformSetting::updateOrCreate(
            ['key' => 'platform_commission'],
            ['value' => '5.00', 'type' => 'decimal', 'group' => 'billing']
        );
        PlatformSetting::clearCache();
    }

    // =========================================================================
    // 1. Earn-first удержание не превышает target
    //    Несколько крупных заработков — deducted_total не может превысить target
    // =========================================================================

    public function test_earn_first_deduction_capped_at_target(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'earn_first_enabled'       => true,
            'earn_first_deduction_pct' => 50,
            'price_uzs'                => 100000, // target = 100000
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'earn_first');
        $this->assertEquals(100000, $subscription->earn_first_target);

        // 5 крупных заработков по 200000 каждый
        // Net = 200000 - 5% commission = 190000
        // Удержание 50% от 190000 = 95000 за каждый
        // Но target = 100000, значит после первого (95000) остаётся 5000
        // Второй удержит только 5000 (cap), остальные — 0
        for ($i = 0; $i < 5; $i++) {
            $this->engine->creditAgencyEarnings($agency, 200000, Str::uuid()->toString(), "Заказ {$i}");
        }

        $subscription->refresh();

        $this->assertLessThanOrEqual(
            $subscription->earn_first_target,
            $subscription->earn_first_deducted_total,
            "Общее удержание ({$subscription->earn_first_deducted_total}) не должно превысить target ({$subscription->earn_first_target})"
        );
        $this->assertEquals(100000, $subscription->earn_first_deducted_total, 'Должен быть удержан ровно target');

        // Проверяем инвариант кошелька
        $wallet = AgencyWallet::where('agency_id', $agency->id)->first();
        $wallet->refresh();
        $expectedBalance = $wallet->total_earned - $wallet->total_deducted - $wallet->total_paid_out;
        $this->assertEquals($expectedBalance, $wallet->balance, 'Баланс кошелька консистентен');

        // Ledger инвариант
        $totalDebit  = LedgerEntry::sum('debit');
        $totalCredit = LedgerEntry::sum('credit');
        $this->assertEquals($totalDebit, $totalCredit, 'Двойная запись балансируется');
    }

    // =========================================================================
    // 2. Anti-abuse: смена плана чаще 1 раза в 24 часа — отказ
    // =========================================================================

    public function test_plan_change_cooldown_24_hours(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'slug'      => 'starter',
            'name'      => 'Starter',
            'price_uzs' => 100000,
            'price_yearly' => 10000,
        ]);
        $this->createPlan([
            'slug'      => 'pro',
            'name'      => 'Pro',
            'price_uzs' => 200000,
            'price_yearly' => 20000,
        ]);

        // Первая смена — OK
        $this->engine->subscribe($agency, 'starter', 'monthly');
        $result = $this->engine->changePlan($agency, 'pro', 'monthly', $owner->id);
        $this->assertEquals('upgrade', $result['type']);

        // Вторая смена в те же 24 часа — ошибка
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('1 раза в 24 часа');
        $this->engine->changePlan($agency, 'starter', 'monthly', $owner->id);
    }

    // =========================================================================
    // 3. Купон для другого плана не применяется
    // =========================================================================

    public function test_coupon_for_different_plan_not_applied(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan(['slug' => 'pro', 'price_uzs' => 200000]);

        // Купон только для enterprise
        Coupon::create([
            'id'             => (string) Str::uuid(),
            'code'           => 'ENTERPRISE_ONLY',
            'description'    => 'Only for enterprise',
            'discount_type'  => 'percentage',
            'discount_value' => 50,
            'max_uses'       => 0,
            'used_count'     => 0,
            'plan_slug'      => 'enterprise', // не совпадает с 'pro'
            'valid_from'     => null,
            'valid_until'    => null,
            'is_active'      => true,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'prepaid', 'ENTERPRISE_ONLY');

        // Скидка не применена
        $this->assertEquals(0, $subscription->discount_amount, 'Купон для другого плана не должен применяться');
        $this->assertNull($subscription->coupon_id);
    }

    // =========================================================================
    // 4. Истёкший купон не применяется
    // =========================================================================

    public function test_expired_coupon_not_applied(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan(['slug' => 'pro', 'price_uzs' => 200000]);

        Coupon::create([
            'id'             => (string) Str::uuid(),
            'code'           => 'EXPIRED',
            'description'    => 'Expired coupon',
            'discount_type'  => 'percentage',
            'discount_value' => 30,
            'max_uses'       => 0,
            'used_count'     => 0,
            'plan_slug'      => null,
            'valid_from'     => now()->subDays(30),
            'valid_until'    => now()->subDays(1), // истёк вчера
            'is_active'      => true,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'prepaid', 'EXPIRED');

        $this->assertEquals(0, $subscription->discount_amount, 'Истёкший купон не должен применяться');
        $this->assertNull($subscription->coupon_id);
    }

    // =========================================================================
    // 5. Купон с max_uses исчерпан — не применяется
    // =========================================================================

    public function test_exhausted_coupon_not_applied(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan(['slug' => 'pro', 'price_uzs' => 200000]);

        Coupon::create([
            'id'             => (string) Str::uuid(),
            'code'           => 'LIMITED',
            'description'    => 'Limited uses',
            'discount_type'  => 'fixed',
            'discount_value' => 50000,
            'max_uses'       => 3,
            'used_count'     => 3, // все использования исчерпаны
            'plan_slug'      => null,
            'valid_from'     => null,
            'valid_until'    => null,
            'is_active'      => true,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'prepaid', 'LIMITED');

        $this->assertEquals(0, $subscription->discount_amount, 'Исчерпанный купон не должен применяться');
        $this->assertNull($subscription->coupon_id);
    }

    // =========================================================================
    // 6. Двойная оплата подписки — повторный paySubscription создаёт вторую транзакцию
    //    (документируем текущее поведение: нет защиты от двойной оплаты)
    // =========================================================================

    public function test_double_subscription_payment_creates_separate_transactions(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan(['price_uzs' => 200000]);
        $this->engine->subscribe($agency, 'pro', 'monthly');

        $tx1 = $this->engine->paySubscription($agency);
        $tx2 = $this->engine->paySubscription($agency);

        $this->assertNotEquals($tx1->id, $tx2->id, 'Каждая оплата создаёт отдельную транзакцию');

        // Ledger всё ещё балансируется
        $totalDebit  = LedgerEntry::sum('debit');
        $totalCredit = LedgerEntry::sum('credit');
        $this->assertEquals($totalDebit, $totalCredit, 'Двойная запись балансируется даже при двойной оплате');
    }

    // =========================================================================
    // 7. Earn-first с нулевой ценой — target = 0, удержания не происходит
    // =========================================================================

    public function test_earn_first_with_zero_target_no_deductions(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'earn_first_enabled'       => true,
            'earn_first_deduction_pct' => 20,
            'price_uzs'                => 0, // бесплатный план
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'earn_first');
        $this->assertEquals(0, $subscription->earn_first_target);

        // Заработок — удержания не будет
        $this->engine->creditAgencyEarnings($agency, 100000, Str::uuid()->toString(), 'Тест');

        $subscription->refresh();
        $this->assertEquals(0, $subscription->earn_first_deducted_total);

        $wallet = AgencyWallet::where('agency_id', $agency->id)->first();
        $wallet->refresh();
        $this->assertEquals(0, $wallet->total_deducted);
    }
}
