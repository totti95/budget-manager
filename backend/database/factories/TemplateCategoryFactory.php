<?php

namespace Database\Factories;

use App\Models\BudgetTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_template_id' => BudgetTemplate::factory(),
            'name' => fake()->words(2, true),
            'sort_order' => 0,
            'planned_amount_cents' => fake()->numberBetween(10000, 100000),
        ];
    }
}
