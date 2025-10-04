<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        if (!$template) {
            return response()->json([
                'message' => 'Aucun template par défaut trouvé. Veuillez en créer un.',
            ], 404);
        }

        // Create budget from template
        $budget = $request->user()->budgets()->create([
            'month' => $month,
            'name' => 'Budget ' . $month->isoFormat('MMMM YYYY'),
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
                $budgetCat->subcategories()->create([
                    'name' => $templateSubcat->name,
                    'planned_amount_cents' => $templateSubcat->planned_amount_cents,
                    'sort_order' => $templateSubcat->sort_order,
                ]);
            }
        }

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
}
