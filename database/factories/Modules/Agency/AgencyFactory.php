<?php

namespace Database\Factories\Modules\Agency;

use App\Modules\Agency\Enums\Plan;
use App\Modules\Agency\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'id'         => (string) Str::uuid(),
            'name'       => $name,
            'slug'       => Str::slug($name) . '-' . Str::random(4),
            'email'      => fake()->unique()->companyEmail(),
            'phone'      => fake()->phoneNumber(),
            'country'    => 'UZ',
            'timezone'   => 'Asia/Tashkent',
            'plan'       => Plan::Pro,
            'is_active'  => true,
            'is_verified' => true,
        ];
    }

    public function trial(): static
    {
        return $this->state(['plan' => Plan::Trial]);
    }

    public function starter(): static
    {
        return $this->state(['plan' => Plan::Starter]);
    }

    public function pro(): static
    {
        return $this->state(['plan' => Plan::Pro]);
    }

    public function business(): static
    {
        return $this->state(['plan' => Plan::Business]);
    }

    public function enterprise(): static
    {
        return $this->state(['plan' => Plan::Enterprise]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
