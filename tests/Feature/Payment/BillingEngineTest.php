<?php

namespace Tests\Feature\Payment;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\AgencyWallet;
use App\Modules\Payment\Models\BillingEvent;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\Coupon;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\PlatformSetting;
use App\Modules\Payment\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingEngineTest extends TestCase
{
    use RefreshDatabase;

    private BillingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(BillingEngine::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

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

    private function createCoupon(array $attrs = []): Coupon
    {
        return Coupon::create(array_merge([
            'id'             => (string) Str::uuid(),
            'code'           => 'TESTCODE',
            'description'    => 'Test coupon',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'max_uses'       => 0,
            'used_count'     => 0,
            'plan_slug'      => null,
            'valid_from'     => null,
            'valid_until'    => null,
            'is_active'      => true,
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
    // subscribe() — creates subscription with correct dates
    // =========================================================================

    public function test_subscribe_creates_subscription_with_correct_dates(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $plan = $this->createPlan();

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly');

        $this->assertInstanceOf(AgencySubscription::class, $subscription);
        $this->assertEquals($agency->id, $subscription->agency_id);
        $this->assertEquals('pro', $subscription->plan_slug);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals('monthly', $subscription->billing_period);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->expires_at);
        $this->assertNotNull($subscription->grace_ends_at);

        // Monthly = 30 days
        $expectedExpires = now()->addDays(30);
        $this->assertEquals(
            $expectedExpires->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d')
        );

        // Grace period = plan's grace_period_days after expiry
        $expectedGrace = $expectedExpires->copy()->addDays($plan->grace_period_days);
        $this->assertEquals(
            $expectedGrace->format('Y-m-d'),
            $subscription->grace_ends_at->format('Y-m-d')
        );

        // Agency plan synced
        $agency->refresh();
        $this->assertEquals('pro', $agency->plan->value);
    }

    // =========================================================================
    // subscribe() — monthly vs yearly period calculation
    // =========================================================================

    public function test_subscribe_monthly_vs_yearly_period_calculation(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan();

        // Monthly
        $monthly = $this->engine->subscribe($agency, 'pro', 'monthly');
        $this->assertEquals(
            now()->addDays(30)->format('Y-m-d'),
            $monthly->expires_at->format('Y-m-d')
        );

        // Clean up for yearly test
        AgencySubscription::where('agency_id', $agency->id)->delete();

        // Yearly
        $yearly = $this->engine->subscribe($agency, 'pro', 'yearly');
        $this->assertEquals(
            now()->addDays(365)->format('Y-m-d'),
            $yearly->expires_at->format('Y-m-d')
        );
    }

    // =========================================================================
    // subscribe() — coupon with percentage discount applied correctly
    // =========================================================================

    public function test_subscribe_with_percentage_coupon(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $plan = $this->createPlan(['price_uzs' => 200000]);
        $coupon = $this->createCoupon([
            'code'           => 'SAVE20',
            'discount_type'  => 'percentage',
            'discount_value' => 20,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'prepaid', 'SAVE20');

        // 20% of 200000 = 40000
        $this->assertEquals(40000, $subscription->discount_amount);
        $this->assertEquals($coupon->id, $subscription->coupon_id);

        // Coupon usage incremented
        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);
    }

    // =========================================================================
    // subscribe() — coupon with fixed discount applied correctly
    // =========================================================================

    public function test_subscribe_with_fixed_coupon(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan(['price_uzs' => 200000]);
        $coupon = $this->createCoupon([
            'code'           => 'FLAT50K',
            'discount_type'  => 'fixed',
            'discount_value' => 50000,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'prepaid', 'FLAT50K');

        // Fixed 50000
        $this->assertEquals(50000, $subscription->discount_amount);
        $this->assertEquals($coupon->id, $subscription->coupon_id);
    }

    // =========================================================================
    // cancelCurrentSubscription() — sets status cancelled and cancelled_at
    // =========================================================================

    public function test_cancel_subscription_sets_cancelled_status(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan();
        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly');

        $this->assertEquals('active', $subscription->status);

        $result = $this->engine->cancelSubscription($agency);

        $this->assertTrue($result);

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
        $this->assertFalse($subscription->auto_renew);
    }

    // =========================================================================
    // calculateVat() — subtotal, vat, discount, total calculated correctly
    // =========================================================================

    public function test_calculate_vat_when_enabled(): void
    {
        PlatformSetting::updateOrCreate(
            ['key' => 'vat_enabled'],
            ['value' => 'true', 'type' => 'boolean', 'group' => 'billing']
        );
        PlatformSetting::updateOrCreate(
            ['key' => 'vat_rate'],
            ['value' => '12.00', 'type' => 'decimal', 'group' => 'billing']
        );

        // Clear cache so PlatformSetting::get picks up new values
        PlatformSetting::clearCache();

        $result = $this->engine->calculateVat(100000);

        $this->assertEquals(12000, $result['vat']);
        $this->assertEquals(12, $result['rate']);
    }

    public function test_calculate_vat_when_disabled(): void
    {
        $this->seedPlatformSettings(); // vat_enabled = false
        PlatformSetting::clearCache();

        $result = $this->engine->calculateVat(100000);

        $this->assertEquals(0, $result['vat']);
        $this->assertEquals(0, $result['rate']);
    }

    // =========================================================================
    // checkLimits() — returns valid when within limits
    // =========================================================================

    public function test_check_limits_within_limits(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'slug'         => 'pro',
            'max_managers' => 5,
            'max_cases'    => 100,
        ]);

        // Sync agency plan
        $agency->update(['plan' => 'pro']);

        $result = $this->engine->checkLimits($agency);

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['can_add_manager']);
        $this->assertTrue($result['can_add_case']);
        // 1 owner already created
        $this->assertEquals(1, $result['managers_count']);
        $this->assertEquals(5, $result['max_managers']);
        $this->assertEquals(0, $result['cases_count']);
        $this->assertEquals(100, $result['max_cases']);
    }

    // =========================================================================
    // checkLimits() — returns exceeded when over max_cases
    // =========================================================================

    public function test_check_limits_exceeded_max_cases(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);
        $client = $this->createClient($agency);

        $this->createPlan([
            'slug'         => 'pro',
            'max_managers' => 5,
            'max_cases'    => 2,
        ]);

        $agency->update(['plan' => 'pro']);

        // Create 3 active cases (exceeds limit of 2)
        for ($i = 0; $i < 3; $i++) {
            VisaCase::factory()->create([
                'agency_id' => $agency->id,
                'client_id' => $client->id,
                'stage'     => 'qualification',
            ]);
        }

        $result = $this->engine->checkLimits($agency);

        $this->assertTrue($result['valid']); // valid means plan was found
        $this->assertEquals(3, $result['cases_count']);
        $this->assertEquals(2, $result['max_cases']);
        $this->assertFalse($result['can_add_case']);
    }

    // =========================================================================
    // checkLimits() — returns exceeded when over max_managers
    // =========================================================================

    public function test_check_limits_exceeded_max_managers(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'slug'         => 'pro',
            'max_managers' => 2,
            'max_cases'    => 100,
        ]);

        $agency->update(['plan' => 'pro']);

        // owner (role=owner) counts as 1, add 2 more managers = 3 total
        $this->createManager($agency);
        $this->createManager($agency);

        $result = $this->engine->checkLimits($agency);

        $this->assertTrue($result['valid']);
        $this->assertEquals(3, $result['managers_count']); // 1 owner + 2 managers
        $this->assertEquals(2, $result['max_managers']);
        $this->assertFalse($result['can_add_manager']);
    }

    // =========================================================================
    // changePlan() — upgrade creates new subscription
    // =========================================================================

    public function test_change_plan_upgrade_creates_new_subscription(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        // Create two plans: starter (cheaper) and pro (more expensive)
        $starterPlan = $this->createPlan([
            'slug'      => 'starter',
            'name'      => 'Starter',
            'price_uzs' => 100000,
            'price_yearly' => 10000,
        ]);
        $proPlan = $this->createPlan([
            'slug'      => 'pro',
            'name'      => 'Pro',
            'price_uzs' => 200000,
            'price_yearly' => 20000,
        ]);

        // Subscribe to starter first
        $oldSub = $this->engine->subscribe($agency, 'starter', 'monthly');
        $this->assertEquals('active', $oldSub->status);

        // Upgrade to pro
        $result = $this->engine->changePlan($agency, 'pro', 'monthly', $owner->id);

        $this->assertEquals('upgrade', $result['type']);
        $this->assertInstanceOf(AgencySubscription::class, $result['subscription']);
        $this->assertEquals('pro', $result['subscription']->plan_slug);
        $this->assertEquals('active', $result['subscription']->status);

        // Old subscription should be cancelled
        $oldSub->refresh();
        $this->assertEquals('cancelled', $oldSub->status);

        // Agency plan synced
        $agency->refresh();
        $this->assertEquals('pro', $agency->plan->value);
    }

    // =========================================================================
    // changePlan() — downgrade schedules pending downgrade
    // =========================================================================

    public function test_change_plan_downgrade_schedules_pending(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        // Create two plans
        $this->createPlan([
            'slug'      => 'starter',
            'name'      => 'Starter',
            'price_uzs' => 100000,
            'price_yearly' => 10000,
            'max_managers' => 3,
            'max_cases' => 50,
        ]);
        $this->createPlan([
            'slug'      => 'pro',
            'name'      => 'Pro',
            'price_uzs' => 200000,
            'price_yearly' => 20000,
        ]);

        // Subscribe to pro first
        $sub = $this->engine->subscribe($agency, 'pro', 'monthly');

        // Downgrade to starter
        $result = $this->engine->changePlan($agency, 'starter', 'monthly', $owner->id);

        $this->assertEquals('downgrade_scheduled', $result['type']);
        $this->assertNotNull($result['change_at']);
        $this->assertIsArray($result['warnings']);

        // Subscription should have pending downgrade info
        $sub->refresh();
        $this->assertEquals('starter', $sub->pending_plan_slug);
        $this->assertEquals('monthly', $sub->pending_billing_period);
        $this->assertNotNull($sub->pending_change_at);

        // Original subscription should still be active
        $this->assertContains($sub->status, ['active', 'trialing']);
    }

    // =========================================================================
    // subscribe() — trial status when plan has trial_days
    // =========================================================================

    public function test_subscribe_trial_status_when_plan_has_trial_days(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'trial_days' => 14,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly');

        $this->assertEquals('trialing', $subscription->status);
        $this->assertEquals(
            now()->addDays(14)->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d')
        );
    }

    // =========================================================================
    // subscribe() — cancels existing subscription before creating new one
    // =========================================================================

    public function test_subscribe_cancels_existing_subscription(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan();

        $first = $this->engine->subscribe($agency, 'pro', 'monthly');
        $this->assertEquals('active', $first->status);

        $second = $this->engine->subscribe($agency, 'pro', 'yearly');

        // First subscription should be cancelled
        $first->refresh();
        $this->assertEquals('cancelled', $first->status);

        // Second is the active one
        $this->assertContains($second->status, ['active', 'trialing']);
    }

    // =========================================================================
    // subscribe() — creates wallet for agency
    // =========================================================================

    public function test_subscribe_creates_wallet(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan();

        $this->engine->subscribe($agency, 'pro', 'monthly');

        $wallet = AgencyWallet::where('agency_id', $agency->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(0, $wallet->balance);
    }

    // =========================================================================
    // subscribe() — creates invoice for prepaid non-trial
    // =========================================================================

    public function test_subscribe_creates_invoice_for_prepaid(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'trial_days' => 0,
            'price_uzs'  => 200000,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'prepaid');

        $invoice = Invoice::where('subscription_id', $subscription->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('issued', $invoice->status);
        $this->assertEquals(200000, $invoice->subtotal);
        $this->assertEquals($agency->id, $invoice->agency_id);
    }

    // =========================================================================
    // cancelSubscription() — returns false when no active subscription
    // =========================================================================

    public function test_cancel_subscription_returns_false_when_none(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $result = $this->engine->cancelSubscription($agency);

        $this->assertFalse($result);
    }

    // =========================================================================
    // checkLimits() — returns invalid when plan not found
    // =========================================================================

    public function test_check_limits_returns_invalid_when_plan_not_found(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        // Agency has plan 'pro' by default but no BillingPlan record
        $result = $this->engine->checkLimits($agency);

        $this->assertFalse($result['valid']);
        $this->assertEquals('Plan not found', $result['reason']);
    }

    // =========================================================================
    // checkLimits() — unlimited plan returns null for max values
    // =========================================================================

    public function test_check_limits_unlimited_plan(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'slug'                => 'pro',
            'max_managers'        => 0,
            'max_cases'           => 0,
            'max_leads_per_month' => 0,
        ]);

        $agency->update(['plan' => 'pro']);

        $result = $this->engine->checkLimits($agency);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['max_managers']);
        $this->assertNull($result['max_cases']);
        $this->assertNull($result['max_leads']);
        $this->assertTrue($result['can_add_manager']);
        $this->assertTrue($result['can_add_case']);
        $this->assertTrue($result['can_accept_lead']);
    }

    // =========================================================================
    // BillingEvent logged on subscribe
    // =========================================================================

    public function test_billing_event_logged_on_subscribe(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan();

        $this->engine->subscribe($agency, 'pro', 'monthly', 'prepaid', null, $owner->id);

        $event = BillingEvent::where('agency_id', $agency->id)
            ->where('event', 'subscription.created')
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals($owner->id, $event->actor_id);
        $this->assertEquals('agency_subscription', $event->entity_type);
    }

    // =========================================================================
    // earn_first target calculated on subscribe
    // =========================================================================

    public function test_subscribe_earn_first_sets_target(): void
    {
        $this->seedPlatformSettings();
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        $this->createPlan([
            'earn_first_enabled'       => true,
            'earn_first_deduction_pct' => 20,
            'price_uzs'                => 200000,
        ]);

        $subscription = $this->engine->subscribe($agency, 'pro', 'monthly', 'earn_first');

        $this->assertEquals(200000, $subscription->earn_first_target);
        $this->assertEquals(0, $subscription->earn_first_deducted_total);
        $this->assertEquals('earn_first', $subscription->payment_model);
    }
}
