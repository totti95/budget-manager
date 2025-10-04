<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetSubcategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'budget_subcategory_id' => BudgetSubcategory::factory(),
            'date' => Carbon::now(),
            'label' => fake()->sentence(3),
            'amount_cents' => fake()->numberBetween(500, 10000),
            'payment_method' => fake()->randomElement(['CB', 'Espèces', 'Virement', 'Prélèvement']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
