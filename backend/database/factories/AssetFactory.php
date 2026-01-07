<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['savings', 'investment', 'real_estate', 'cash', 'other']),
            'is_liability' => false,
            'label' => fake()->words(3, true),
            'institution' => fake()->optional()->company(),
            'value_cents' => fake()->numberBetween(10000, 500000),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_liability' => true,
            'type' => fake()->randomElement(['loan', 'mortgage', 'credit_card', 'other']),
        ]);
    }
}
