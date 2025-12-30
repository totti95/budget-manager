<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\SavingsPlan;
use App\Services\RecurringExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->budgets()->with('categories.subcategories');

        if ($request->has('month')) {
            // Convert Y-m format to full date for comparison
            $monthDate = Carbon::parse($request->month.'-01');
            $query->whereYear('month', $monthDate->year)
                ->whereMonth('month', $monthDate->month);
        }

        $budgets = $query->orderBy('month', 'desc')->paginate(12);

        return response()->json($budgets);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $month = Carbon::parse($validated['month'].'-01');

        // Check if budget already exists
        $existing = $request->user()->budgets()->where('month', $month)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Un budget existe déjà pour ce mois',
                'budget' => $existing,
            ], 409);
        }

        // Get default template
        $template = $request->user()->defaultTemplate();
        if (! $template) {
            return response()->json([
                'message' => 'Aucun template par défaut trouvé. Veuillez en créer un.',
            ], 404);
        }

        // Create budget from template
        $budget = $request->user()->budgets()->create([
            'month' => $month,
            'name' => 'Budget '.$month->isoFormat('MMMM YYYY'),
            'generated_from_template_id' => $template->id,
        ]);

        // Copy categories from template
        foreach ($template->categories as $templateCat) {
            $budgetCat = $budget->categories()->create([
                'name' => $templateCat->name,
                'planned_amount_cents' => $templateCat->planned_amount_cents,
                'sort_order' => $templateCat->sort_order,
            ]);

            // Copy subcategories
            foreach ($templateCat->subcategories as $templateSubcat) {
                $budgetSubcat = $budgetCat->subcategories()->create([
                    'name' => $templateSubcat->name,
                    'planned_amount_cents' => $templateSubcat->planned_amount_cents,
                    'sort_order' => $templateSubcat->sort_order,
                    'default_spent_cents' => $templateSubcat->default_spent_cents ?? 0,
                ]);

                // Create default expense if default_spent_cents is set
                if (($templateSubcat->default_spent_cents ?? 0) > 0) {
                    $budgetSubcat->expenses()->create([
                        'budget_id' => $budget->id,
                        'label' => $templateSubcat->name,
                        'amount_cents' => $templateSubcat->default_spent_cents,
                        'date' => $month,
                    ]);
                }
            }
        }

        // Create or update SavingsPlan for this month
        // Calculate planned savings from "Épargne" category if exists
        $savingsCategory = $budget->categories()->where('name', 'Épargne')->first();
        $plannedCents = 0;

        if ($savingsCategory) {
            $plannedCents = $savingsCategory->subcategories->sum('planned_amount_cents');
        }

        SavingsPlan::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'month' => $month,
            ],
            [
                'planned_cents' => $plannedCents,
            ]
        );

        // Create recurring expenses for this budget
        $recurringExpensesCreated = app(RecurringExpenseService::class)
            ->createRecurringExpensesForBudget($budget);

        Log::info('Budget generated with recurring expenses', [
            'budget_id' => $budget->id,
            'recurring_expenses_created' => $recurringExpensesCreated,
        ]);

        return response()->json($budget->load('categories.subcategories'), 201);
    }

    public function show(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        $budget->load([
            'categories.subcategories.expenses',
            'expenses',
        ]);

        return response()->json($budget);
    }

    public function update(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $budget->update($validated);

        return response()->json($budget);
    }

    public function destroy(Request $request, Budget $budget)
    {
        $this->authorize('delete', $budget);

        $budget->delete();

        return response()->json(null, 204);
    }

    public function compare(Request $request)
    {
        $validated = $request->validate([
            'months' => 'required|array|min:2|max:3',
            'months.*' => 'required|date_format:Y-m',
        ]);

        $user = $request->user();
        $budgets = [];
        $categoryData = [];

        foreach ($validated['months'] as $month) {
            $monthDate = Carbon::parse($month.'-01');
            $budget = $user->budgets()
                ->where('month', $monthDate)
                ->with(['categories.subcategories.expenses'])
                ->first();

            if ($budget) {
                // Calculate stats for this budget
                $totalPlanned = 0;
                $totalActual = 0;
                $byCategory = [];

                foreach ($budget->categories as $category) {
                    $categoryPlanned = $category->planned_amount_cents;
                    $categoryActual = 0;

                    foreach ($category->subcategories as $subcategory) {
                        $subcategoryActual = $subcategory->expenses->sum('amount_cents');
                        $categoryActual += $subcategoryActual;
                    }

                    $totalPlanned += $categoryPlanned;
                    $totalActual += $categoryActual;

                    $byCategory[] = [
                        'name' => $category->name,
                        'planned_cents' => $categoryPlanned,
                        'actual_cents' => $categoryActual,
                        'variance_cents' => $categoryActual - $categoryPlanned,
                        'variance_percent' => $categoryPlanned > 0
                            ? round((($categoryActual - $categoryPlanned) / $categoryPlanned) * 100, 2)
                            : 0,
                    ];

                    // Store for evolution calculation
                    if (! isset($categoryData[$category->name])) {
                        $categoryData[$category->name] = [];
                    }
                    $categoryData[$category->name][] = $categoryActual;
                }

                $budget->stats = [
                    'total_planned_cents' => $totalPlanned,
                    'total_actual_cents' => $totalActual,
                    'variance_cents' => $totalActual - $totalPlanned,
                    'variance_percent' => $totalPlanned > 0
                        ? round((($totalActual - $totalPlanned) / $totalPlanned) * 100, 2)
                        : 0,
                    'by_category' => $byCategory,
                ];

                $budgets[] = $budget;
            }
        }

        // Calculate evolution across months
        $evolution = [];
        foreach ($categoryData as $categoryName => $values) {
            if (count($values) >= 2) {
                $firstValue = $values[0];
                $lastValue = $values[count($values) - 1];
                $evolutionPercent = $firstValue > 0
                    ? round((($lastValue - $firstValue) / $firstValue) * 100, 2)
                    : 0;

                $evolution[] = [
                    'category_name' => $categoryName,
                    'values' => $values,
                    'evolution_percent' => $evolutionPercent,
                ];
            }
        }

        return response()->json([
            'budgets' => $budgets,
            'comparison' => [
                'evolution' => $evolution,
            ],
        ]);
    }
}
