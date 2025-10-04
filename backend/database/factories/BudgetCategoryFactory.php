<?php

namespace Database\Factories;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'name' => fake()->word(),
            'sort_order' => 0,
            'planned_amount_cents' => fake()->numberBetween(5000, 50000),
        ];
    }
}
