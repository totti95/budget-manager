<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringExpenseFactory extends Factory
{
    public function definition(): array
    {
        $frequency = fake()->randomElement(['monthly', 'weekly', 'yearly']);

        $data = [
            'user_id' => User::factory(),
            'template_subcategory_id' => null,
            'label' => fake()->words(3, true),
            'amount_cents' => fake()->numberBetween(1000, 50000),
            'frequency' => $frequency,
            'auto_create' => true,
            'is_active' => true,
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => null,
            'payment_method' => fake()->optional()->randomElement(['CB', 'Prélèvement', 'Virement']),
            'notes' => fake()->optional()->sentence(),
        ];

        // Add frequency-specific fields
        if ($frequency === 'monthly') {
            $data['day_of_month'] = fake()->numberBetween(1, 28);
            $data['day_of_week'] = null;
            $data['month_of_year'] = null;
        } elseif ($frequency === 'weekly') {
            $data['day_of_month'] = null;
            $data['day_of_week'] = fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $data['month_of_year'] = null;
        } elseif ($frequency === 'yearly') {
            $data['day_of_month'] = fake()->numberBetween(1, 28);
            $data['day_of_week'] = null;
            $data['month_of_year'] = fake()->numberBetween(1, 12);
        }

        return $data;
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
            'day_of_month' => fake()->numberBetween(1, 28),
            'day_of_week' => null,
            'month_of_year' => null,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
            'day_of_month' => null,
            'day_of_week' => fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'month_of_year' => null,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'yearly',
            'day_of_month' => fake()->numberBetween(1, 28),
            'day_of_week' => null,
            'month_of_year' => fake()->numberBetween(1, 12),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
