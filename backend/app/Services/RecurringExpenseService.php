<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\RecurringExpense;
use Illuminate\Support\Facades\Log;

class RecurringExpenseService
{
    /**
     * Create expenses from all active recurring expenses for a budget.
     *
     * @param Budget $budget
     *
     * @return int Number of expenses created
     */
    public function createRecurringExpensesForBudget(Budget $budget): int
    {
        $user = $budget->user;
        $month = $budget->month; // Carbon instance
        $created = 0;

        // Get all active recurring expenses for this user
        $recurringExpenses = RecurringExpense::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('auto_create', true)
            ->with('templateSubcategory.templateCategory')
            ->get();

        foreach ($recurringExpenses as $recurring) {
            if (! $recurring->shouldCreateForMonth($month)) {
                continue;
            }

            // Find the target budget_subcategory_id
            $budgetSubcategoryId = null;

            if ($recurring->template_subcategory_id) {
                // Match by name: find budget subcategory with same name as template subcategory
                $templateSubcat = $recurring->templateSubcategory;

                if ($templateSubcat) {
                    $budgetSubcategoryId = $this->findMatchingBudgetSubcategory(
                        $budget,
                        $templateSubcat->name,
                        $templateSubcat->templateCategory->name ?? null
                    );
                }
            }

            // If no match found or no template_subcategory_id, skip
            if (! $budgetSubcategoryId) {
                Log::warning('RecurringExpense: Could not find matching subcategory', [
                    'recurring_expense_id' => $recurring->id,
                    'budget_id' => $budget->id,
                    'template_subcategory_id' => $recurring->template_subcategory_id,
                ]);
                continue;
            }

            // Create the expense
            $expenseDate = $recurring->getExpenseDateForMonth($month);

            $budget->expenses()->create([
                'budget_subcategory_id' => $budgetSubcategoryId,
                'date' => $expenseDate,
                'label' => $recurring->label,
                'amount_cents' => $recurring->amount_cents,
                'payment_method' => $recurring->payment_method,
                'notes' => $recurring->notes ? $recurring->notes . ' (récurrent)' : 'Dépense récurrente',
            ]);

            $created++;

            Log::info('RecurringExpense: Created expense', [
                'recurring_expense_id' => $recurring->id,
                'budget_id' => $budget->id,
                'amount_cents' => $recurring->amount_cents,
            ]);
        }

        return $created;
    }

    /**
     * Find budget subcategory matching template subcategory name.
     * Matches by subcategory name and optionally category name for accuracy.
     *
     * @param Budget $budget
     * @param string $subcategoryName
     * @param string|null $categoryName
     *
     * @return int|null budget_subcategory_id
     */
    private function findMatchingBudgetSubcategory(
        Budget $budget,
        string $subcategoryName,
        ?string $categoryName = null
    ): ?int {
        // Load all budget structure if not already loaded
        $budget->load('categories.subcategories');

        foreach ($budget->categories as $category) {
            // If category name provided, match it first
            if ($categoryName && $category->name !== $categoryName) {
                continue;
            }

            foreach ($category->subcategories as $subcategory) {
                if ($subcategory->name === $subcategoryName) {
                    return $subcategory->id;
                }
            }
        }

        return null;
    }
}
