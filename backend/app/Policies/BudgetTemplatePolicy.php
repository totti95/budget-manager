<?php

namespace App\Policies;

use App\Models\BudgetTemplate;
use App\Models\User;

class BudgetTemplatePolicy
{
    public function view(User $user, BudgetTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function update(User $user, BudgetTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function delete(User $user, BudgetTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }
}
