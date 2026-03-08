<?php

namespace Database\Factories\Modules\Case;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VisaCaseFactory extends Factory
{
    protected $model = VisaCase::class;

    public function definition(): array
    {
        return [
            'id'           => (string) Str::uuid(),
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
            'stage'        => 'lead',
            'priority'     => 'normal',
            'travel_date'  => now()->addMonths(2),
            'notes'        => fake()->sentence(),
        ];
    }

    public function stage(string $stage): static
    {
        return $this->state(['stage' => $stage]);
    }

    public function urgent(): static
    {
        return $this->state(['priority' => 'urgent']);
    }

    public function withCriticalDate(): static
    {
        return $this->state(['critical_date' => now()->addDays(3)]);
    }
}
