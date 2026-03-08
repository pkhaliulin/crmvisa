<?php

namespace Database\Factories\Modules\Payment;

use App\Modules\Payment\Models\BillingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingPlanFactory extends Factory
{
    protected $model = BillingPlan::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'slug'                     => $slug,
            'name'                     => ucfirst($slug),
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
        ];
    }

    public function withSlug(string $slug): static
    {
        return $this->state(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    public function withTrial(int $days = 14): static
    {
        return $this->state(['trial_days' => $days]);
    }

    public function earnFirst(int $pct = 20): static
    {
        return $this->state([
            'earn_first_enabled'       => true,
            'earn_first_deduction_pct' => $pct,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state([
            'max_managers'        => 0,
            'max_cases'           => 0,
            'max_leads_per_month' => 0,
        ]);
    }
}
