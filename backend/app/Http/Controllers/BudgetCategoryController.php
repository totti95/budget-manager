<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use Illuminate\Http\Request;

class BudgetCategoryController extends Controller
{
    public function store(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'planned_amount_cents' => 'required|integer|min:0',
            'sort_order' => 'integer|min:0',
        ]);

        $category = $budget->categories()->create($validated);

        return response()->json($category, 201);
    }

    public function update(Request $request, Budget $budget, BudgetCategory $category)
    {
        $this->authorize('update', $budget);

        if ($category->budget_id !== $budget->id) {
            return response()->json(['message' => 'Catégorie non trouvée dans ce budget'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'planned_amount_cents' => 'sometimes|integer|min:0',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(Request $request, Budget $budget, BudgetCategory $category)
    {
        $this->authorize('update', $budget);

        if ($category->budget_id !== $budget->id) {
            return response()->json(['message' => 'Catégorie non trouvée dans ce budget'], 404);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
