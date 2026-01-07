<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingsGoalFactory extends Factory
{
    public function definition(): array
    {
        $startDate = Carbon::now()->subMonths(2);
        $targetDate = Carbon::now()->addMonths(10);

        return [
            'user_id' => User::factory(),
            'asset_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'target_amount_cents' => fake()->numberBetween(50000, 1000000),
            'current_amount_cents' => fake()->numberBetween(0, 50000),
            'start_date' => $startDate,
            'target_date' => $targetDate,
            'status' => 'active',
            'priority' => fake()->numberBetween(1, 10),
            'notify_milestones' => fake()->boolean(),
            'notify_risk' => fake()->boolean(),
            'notify_reminder' => fake()->boolean(),
            'reminder_day_of_month' => fake()->optional()->numberBetween(1, 28),
            'suggested_monthly_amount_cents' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'current_amount_cents' => $attributes['target_amount_cents'],
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'abandoned',
        ]);
    }
}
