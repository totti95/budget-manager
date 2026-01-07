<?php

namespace Database\Factories;

use App\Models\SavingsGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingsGoalContributionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'savings_goal_id' => SavingsGoal::factory(),
            'user_id' => User::factory(),
            'amount_cents' => fake()->numberBetween(5000, 50000),
            'contribution_date' => Carbon::now(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
