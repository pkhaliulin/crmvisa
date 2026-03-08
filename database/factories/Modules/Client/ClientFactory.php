<?php

namespace Database\Factories\Modules\Client;

use App\Modules\Client\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'id'          => (string) Str::uuid(),
            'name'        => fake()->name(),
            'email'       => fake()->unique()->safeEmail(),
            'phone'       => fake()->phoneNumber(),
            'nationality' => 'UZB',
            'source'      => 'direct',
        ];
    }

    public function referral(): static
    {
        return $this->state(['source' => 'referral']);
    }

    public function marketplace(): static
    {
        return $this->state(['source' => 'marketplace']);
    }
}
