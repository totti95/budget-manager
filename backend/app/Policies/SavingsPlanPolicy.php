<?php

namespace App\Policies;

use App\Models\SavingsPlan;
use App\Models\User;

class SavingsPlanPolicy
{
    public function view(User $user, SavingsPlan $savingsPlan): bool
    {
        return $user->id === $savingsPlan->user_id;
    }

    public function update(User $user, SavingsPlan $savingsPlan): bool
    {
        return $user->id === $savingsPlan->user_id;
    }
}
