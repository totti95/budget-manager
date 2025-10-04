<?php

namespace Database\Factories;

use App\Models\BudgetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetSubcategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_category_id' => BudgetCategory::factory(),
            'name' => fake()->word(),
            'sort_order' => 0,
            'planned_amount_cents' => fake()->numberBetween(1000, 20000),
        ];
    }
}
