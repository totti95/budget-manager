<?php

namespace App\Repositories;

use App\Contracts\BudgetRepositoryInterface;
use App\Models\Budget;
use App\Models\BudgetTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class BudgetRepository implements BudgetRepositoryInterface
{
    /**
     * Find a budget by user and month
     */
    public function findByUserAndMonth(User $user, string $month): ?Budget
    {
        $monthDate = Carbon::parse($month . '-01');

        return $user->budgets()
            ->where('month', $monthDate)
            ->first();
    }

    /**
     * Get all budgets for a user
     */
    public function getAllForUser(User $user): Collection
    {
        return $user->budgets()
            ->orderBy('month', 'desc')
            ->get();
    }

    /**
     * Create a budget from a template
     */
    public function createFromTemplate(User $user, int $templateId, string $month): Budget
    {
        $template = BudgetTemplate::with('categories.subcategories')
            ->findOrFail($templateId);

        $monthDate = Carbon::parse($month . '-01');

        // Create the budget
        $budget = Budget::create([
            'user_id' => $user->id,
            'month' => $monthDate,
            'revenue_cents' => $template->revenue_cents,
        ]);

        // Copy categories and subcategories from template
        foreach ($template->categories as $templateCategory) {
            $category = $budget->categories()->create([
                'name' => $templateCategory->name,
                'planned_amount_cents' => $templateCategory->planned_amount_cents,
                'sort_order' => $templateCategory->sort_order,
            ]);

            foreach ($templateCategory->subcategories as $templateSubcategory) {
                $category->subcategories()->create([
                    'name' => $templateSubcategory->name,
                    'planned_amount_cents' => $templateSubcategory->planned_amount_cents,
                    'sort_order' => $templateSubcategory->sort_order,
                ]);
            }
        }

        return $budget->load('categories.subcategories');
    }

    /**
     * Create a budget with categories
     */
    public function create(User $user, array $data): Budget
    {
        $monthDate = Carbon::parse($data['month'] . '-01');

        $budget = Budget::create([
            'user_id' => $user->id,
            'month' => $monthDate,
            'revenue_cents' => $data['revenue_cents'] ?? 0,
        ]);

        if (isset($data['categories'])) {
            foreach ($data['categories'] as $categoryData) {
                $category = $budget->categories()->create([
                    'name' => $categoryData['name'],
                    'planned_amount_cents' => $categoryData['planned_amount_cents'],
                    'sort_order' => $categoryData['sort_order'] ?? 0,
                ]);

                if (isset($categoryData['subcategories'])) {
                    foreach ($categoryData['subcategories'] as $subcategoryData) {
                        $category->subcategories()->create([
                            'name' => $subcategoryData['name'],
                            'planned_amount_cents' => $subcategoryData['planned_amount_cents'],
                            'sort_order' => $subcategoryData['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        }

        return $budget->load('categories.subcategories');
    }

    /**
     * Update a budget
     */
    public function update(Budget $budget, array $data): Budget
    {
        if (isset($data['revenue_cents'])) {
            $budget->revenue_cents = $data['revenue_cents'];
        }

        $budget->save();

        return $budget->refresh();
    }

    /**
     * Delete a budget
     */
    public function delete(Budget $budget): bool
    {
        return $budget->delete();
    }

    /**
     * Get budget with full relations loaded
     */
    public function findWithRelations(int $id, array $relations = []): ?Budget
    {
        $defaultRelations = ['categories.subcategories.expenses'];
        $relationsToLoad = ! empty($relations) ? $relations : $defaultRelations;

        return Budget::with($relationsToLoad)->find($id);
    }
}
