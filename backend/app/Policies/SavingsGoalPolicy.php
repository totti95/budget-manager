<?php

namespace App\Policies;

use App\Models\SavingsGoal;
use App\Models\User;

class SavingsGoalPolicy
{
    /**
     * Determine whether the user can view the savings goal.
     */
    public function view(User $user, SavingsGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    /**
     * Determine whether the user can update the savings goal.
     */
    public function update(User $user, SavingsGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    /**
     * Determine whether the user can delete the savings goal.
     */
    public function delete(User $user, SavingsGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }
}
