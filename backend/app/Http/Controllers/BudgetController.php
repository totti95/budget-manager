<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\SavingsPlan;
use App\Services\RecurringExpenseService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
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
            $monthDate = Carbon::parse($request->month . '-01');
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

        $month = Carbon::parse($validated['month'] . '-01');

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

        // Déterminer le revenu à utiliser
        $revenueCents = $template->revenue_cents;

        // Si le template n'a pas de revenu, prendre celui du dernier budget
        if (! $revenueCents) {
            $lastBudget = $request->user()->budgets()
                ->whereNotNull('revenue_cents')
                ->orderBy('month', 'desc')
                ->first();
            $revenueCents = $lastBudget?->revenue_cents;
        }

        // Create budget from template
        $budget = $request->user()->budgets()->create([
            'month' => $month,
            'name' => 'Budget ' . $month->isoFormat('MMMM YYYY'),
            'generated_from_template_id' => $template->id,
            'revenue_cents' => $revenueCents,
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
            'revenue_cents' => 'sometimes|required|integer|min:0',
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
            $monthDate = Carbon::parse($month . '-01');
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

    public function exportPdf(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        $budget->load([
            'categories.subcategories.expenses',
            'expenses',
        ]);

        // Calculate statistics
        $stats = $this->calculateBudgetStats($budget);

        // Get authenticated user
        $user = $request->user();

        // Generate PDF from Blade view
        $pdf = PDF::loadView('budgets.pdf', [
            'budget' => $budget,
            'stats' => $stats,
            'user' => $user,
        ]);

        // Configure PDF options
        $pdf->setPaper('a4');

        // Return PDF download response
        // Add 2 hours to handle timezone (DB stores last day of previous month at 23:00 UTC = first day of next month)
        $filename = 'budget-' . $budget->month->copy()->addHours(2)->format('Y-m') . '.pdf';

        return $pdf->download($filename);
    }

    private function calculateBudgetStats(Budget $budget): array
    {
        $totalPlanned = 0;
        $totalActual = 0;
        $byCategory = [];
        $allExpenses = [];

        foreach ($budget->categories as $category) {
            $categoryPlanned = 0;
            $categoryActual = 0;
            $bySubcategory = [];

            foreach ($category->subcategories as $subcategory) {
                $subcategoryActual = $subcategory->expenses->sum('amount_cents');
                $categoryPlanned += $subcategory->planned_amount_cents;
                $categoryActual += $subcategoryActual;

                $subcategoryVariance = $subcategoryActual - $subcategory->planned_amount_cents;
                $subcategoryVariancePercent = $subcategory->planned_amount_cents > 0
                    ? round((($subcategoryActual - $subcategory->planned_amount_cents) / $subcategory->planned_amount_cents) * 100, 2)
                    : 0;

                $bySubcategory[] = [
                    'name' => $subcategory->name,
                    'planned_cents' => $subcategory->planned_amount_cents,
                    'actual_cents' => $subcategoryActual,
                    'variance_cents' => $subcategoryVariance,
                    'variance_percent' => $subcategoryVariancePercent,
                ];

                // Collect all expenses for top 10
                foreach ($subcategory->expenses as $expense) {
                    $allExpenses[] = [
                        'date' => $expense->date,
                        'label' => $expense->label,
                        'amount_cents' => $expense->amount_cents,
                        'category' => $category->name,
                        'subcategory' => $subcategory->name,
                        'payment_method' => $expense->payment_method,
                    ];
                }
            }

            $totalPlanned += $categoryPlanned;
            $totalActual += $categoryActual;

            $categoryVariance = $categoryActual - $categoryPlanned;
            $categoryVariancePercent = $categoryPlanned > 0
                ? round((($categoryActual - $categoryPlanned) / $categoryPlanned) * 100, 2)
                : 0;

            $byCategory[] = [
                'name' => $category->name,
                'planned_cents' => $categoryPlanned,
                'actual_cents' => $categoryActual,
                'variance_cents' => $categoryVariance,
                'variance_percent' => $categoryVariancePercent,
                'subcategories' => $bySubcategory,
            ];
        }

        // Sort expenses by amount descending and take top 10
        usort($allExpenses, fn ($a, $b) => $b['amount_cents'] <=> $a['amount_cents']);
        $topExpenses = array_slice($allExpenses, 0, 10);

        return [
            'total_planned_cents' => $totalPlanned,
            'total_actual_cents' => $totalActual,
            'variance_cents' => $totalActual - $totalPlanned,
            'variance_percent' => $totalPlanned > 0
                ? round((($totalActual - $totalPlanned) / $totalPlanned) * 100, 2)
                : 0,
            'by_category' => $byCategory,
            'top_expenses' => $topExpenses,
        ];
    }
}
