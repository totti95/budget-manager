<?php

namespace App\Contracts;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Collection;

interface ExpenseRepositoryInterface
{
    /**
     * Get all expenses for a budget
     */
    public function getAllForBudget(Budget $budget): Collection;

    /**
     * Create an expense
     */
    public function create(array $data): Expense;

    /**
     * Update an expense
     */
    public function update(Expense $expense, array $data): Expense;

    /**
     * Delete an expense
     */
    public function delete(Expense $expense): bool;

    /**
     * Find an expense by ID with relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Expense;

    /**
     * Get expenses filtered by date range
     */
    public function getByDateRange(Budget $budget, string $startDate, string $endDate): Collection;

    /**
     * Bulk create expenses
     */
    public function bulkCreate(array $expensesData): Collection;
}
