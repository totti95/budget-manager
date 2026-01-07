<?php

namespace Database\Factories;

use App\Models\TemplateCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateSubcategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_category_id' => TemplateCategory::factory(),
            'name' => fake()->words(2, true),
            'planned_amount_cents' => fake()->numberBetween(5000, 50000),
            'default_spent_cents' => 0,
            'sort_order' => 0,
        ];
    }
}
