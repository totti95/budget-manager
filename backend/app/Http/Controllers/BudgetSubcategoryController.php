<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BudgetSubcategory;
use Illuminate\Http\Request;

class BudgetSubcategoryController extends Controller
{
    public function store(Request $request, Budget $budget, BudgetCategory $category)
    {
        $this->authorize('update', $budget);

        if ($category->budget_id !== $budget->id) {
            return response()->json(['message' => 'Catégorie non trouvée dans ce budget'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'planned_amount_cents' => 'required|integer|min:0',
            'sort_order' => 'integer|min:0',
        ]);

        $subcategory = $category->subcategories()->create($validated);

        return response()->json($subcategory, 201);
    }

    public function update(Request $request, Budget $budget, BudgetSubcategory $subcategory)
    {
        $this->authorize('update', $budget);

        // Verify subcategory belongs to this budget
        if ($subcategory->budgetCategory->budget_id !== $budget->id) {
            return response()->json(['message' => 'Sous-catégorie non trouvée dans ce budget'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'planned_amount_cents' => 'sometimes|integer|min:0',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $subcategory->update($validated);

        return response()->json($subcategory);
    }

    public function destroy(Request $request, Budget $budget, BudgetSubcategory $subcategory)
    {
        $this->authorize('update', $budget);

        if ($subcategory->budgetCategory->budget_id !== $budget->id) {
            return response()->json(['message' => 'Sous-catégorie non trouvée dans ce budget'], 404);
        }

        $subcategory->delete();

        return response()->json(null, 204);
    }
}
