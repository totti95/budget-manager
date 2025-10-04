<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'month' => Carbon::now()->startOfMonth(),
            'name' => 'Budget ' . fake()->monthName() . ' ' . fake()->year(),
            'generated_from_template_id' => null,
        ];
    }
}
