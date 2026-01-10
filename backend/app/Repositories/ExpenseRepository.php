<?php

namespace App\Repositories;

use App\Contracts\ExpenseRepositoryInterface;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Collection;

class ExpenseRepository implements ExpenseRepositoryInterface
{
    /**
     * Get all expenses for a budget
     */
    public function getAllForBudget(Budget $budget): Collection
    {
        return Expense::whereHas('budgetSubcategory', function ($query) use ($budget) {
            $query->whereHas('budgetCategory', function ($categoryQuery) use ($budget) {
                $categoryQuery->where('budget_id', $budget->id);
            });
        })
            ->with(['budgetSubcategory.budgetCategory', 'tags'])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Create an expense
     */
    public function create(array $data): Expense
    {
        $expense = Expense::create([
            'budget_subcategory_id' => $data['budget_subcategory_id'],
            'date' => $data['date'],
            'label' => $data['label'],
            'amount_cents' => $data['amount_cents'],
            'payment_method' => $data['payment_method'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Attach tags if provided
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $expense->tags()->attach($data['tag_ids']);
        }

        return $expense->load(['budgetSubcategory', 'tags']);
    }

    /**
     * Update an expense
     */
    public function update(Expense $expense, array $data): Expense
    {
        $expense->fill([
            'budget_subcategory_id' => $data['budget_subcategory_id'] ?? $expense->budget_subcategory_id,
            'date' => $data['date'] ?? $expense->date,
            'label' => $data['label'] ?? $expense->label,
            'amount_cents' => $data['amount_cents'] ?? $expense->amount_cents,
            'payment_method' => $data['payment_method'] ?? $expense->payment_method,
            'notes' => $data['notes'] ?? $expense->notes,
        ]);

        $expense->save();

        // Sync tags if provided
        if (isset($data['tag_ids'])) {
            $expense->tags()->sync($data['tag_ids']);
        }

        return $expense->refresh()->load(['budgetSubcategory', 'tags']);
    }

    /**
     * Delete an expense
     */
    public function delete(Expense $expense): bool
    {
        return $expense->delete();
    }

    /**
     * Find an expense by ID with relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Expense
    {
        $defaultRelations = ['budgetSubcategory.budgetCategory', 'tags'];
        $relationsToLoad = ! empty($relations) ? $relations : $defaultRelations;

        return Expense::with($relationsToLoad)->find($id);
    }

    /**
     * Get expenses filtered by date range
     */
    public function getByDateRange(Budget $budget, string $startDate, string $endDate): Collection
    {
        return Expense::whereHas('budgetSubcategory', function ($query) use ($budget) {
            $query->whereHas('budgetCategory', function ($categoryQuery) use ($budget) {
                $categoryQuery->where('budget_id', $budget->id);
            });
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['budgetSubcategory', 'tags'])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Bulk create expenses
     */
    public function bulkCreate(array $expensesData): Collection
    {
        $expenses = collect();

        foreach ($expensesData as $data) {
            $expenses->push($this->create($data));
        }

        return $expenses;
    }
}
