<?php

namespace Database\Factories\Modules\Payment;

use App\Modules\Payment\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'id'             => (string) Str::uuid(),
            'code'           => strtoupper(fake()->unique()->lexify('??????')),
            'description'    => 'Test coupon',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'max_uses'       => 0,
            'used_count'     => 0,
            'plan_slug'      => null,
            'valid_from'     => null,
            'valid_until'    => null,
            'is_active'      => true,
        ];
    }

    public function percentage(int $value): static
    {
        return $this->state([
            'discount_type'  => 'percentage',
            'discount_value' => $value,
        ]);
    }

    public function fixed(int $value): static
    {
        return $this->state([
            'discount_type'  => 'fixed',
            'discount_value' => $value,
        ]);
    }

    public function forPlan(string $planSlug): static
    {
        return $this->state(['plan_slug' => $planSlug]);
    }
}
