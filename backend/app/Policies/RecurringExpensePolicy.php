<?php

namespace App\Policies;

use App\Models\RecurringExpense;
use App\Models\User;

class RecurringExpensePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->id === $recurringExpense->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->id === $recurringExpense->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->id === $recurringExpense->user_id;
    }
}
