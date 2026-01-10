<?php

namespace App\Contracts;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface BudgetRepositoryInterface
{
    /**
     * Find a budget by user and month
     */
    public function findByUserAndMonth(User $user, string $month): ?Budget;

    /**
     * Get all budgets for a user
     */
    public function getAllForUser(User $user): Collection;

    /**
     * Create a budget from a template
     */
    public function createFromTemplate(User $user, int $templateId, string $month): Budget;

    /**
     * Create a budget with categories
     */
    public function create(User $user, array $data): Budget;

    /**
     * Update a budget
     */
    public function update(Budget $budget, array $data): Budget;

    /**
     * Delete a budget
     */
    public function delete(Budget $budget): bool;

    /**
     * Get budget with full relations loaded
     */
    public function findWithRelations(int $id, array $relations = []): ?Budget;
}
