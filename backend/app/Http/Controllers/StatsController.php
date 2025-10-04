<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function summary(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        // Charger les relations en une seule requête pour éviter N+1
        $budget->loadMissing(['categories', 'expenses']);

        $totalPlanned = $budget->categories->sum('planned_amount_cents');
        $totalActual = $budget->expenses->sum('amount_cents');
        $variance = $totalActual - $totalPlanned;
        $variancePercentage = $totalPlanned > 0 ? (($totalActual / $totalPlanned - 1) * 100) : null;

        return response()->json([
            'totalPlannedCents' => $totalPlanned,
            'totalActualCents' => $totalActual,
            'varianceCents' => $variance,
            'variancePercentage' => $variancePercentage,
            'expenseCount' => $budget->expenses->count(),
        ]);
    }

    public function byCategory(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        // Charger toutes les relations nécessaires en une seule requête
        $budget->loadMissing('categories.subcategories.expenses');

        $stats = $budget->categories->map(function ($category) {
            $actualCents = $category->subcategories->sum(function ($subcat) {
                return $subcat->expenses->sum('amount_cents');
            });

            $varianceCents = $actualCents - $category->planned_amount_cents;
            $variancePercentage = $category->planned_amount_cents > 0
                ? (($actualCents / $category->planned_amount_cents - 1) * 100)
                : null;

            return [
                'id' => $category->id,
                'name' => $category->name,
                'plannedAmountCents' => $category->planned_amount_cents,
                'actualAmountCents' => $actualCents,
                'varianceCents' => $varianceCents,
                'variancePercentage' => $variancePercentage,
                'expenseCount' => $category->subcategories->sum(function ($subcat) {
                    return $subcat->expenses->count();
                }),
            ];
        });

        return response()->json($stats);
    }

    public function bySubcategory(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        // Charger toutes les relations nécessaires en une seule requête
        $budget->loadMissing('categories.subcategories.expenses');

        $categoryId = $request->query('categoryId');

        $categories = $categoryId
            ? $budget->categories->where('id', $categoryId)
            : $budget->categories;

        $stats = [];

        foreach ($categories as $category) {
            foreach ($category->subcategories as $subcategory) {
                $actualCents = $subcategory->expenses->sum('amount_cents');
                $varianceCents = $actualCents - $subcategory->planned_amount_cents;
                $variancePercentage = $subcategory->planned_amount_cents > 0
                    ? (($actualCents / $subcategory->planned_amount_cents - 1) * 100)
                    : null;

                $stats[] = [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'categoryId' => $category->id,
                    'categoryName' => $category->name,
                    'plannedAmountCents' => $subcategory->planned_amount_cents,
                    'actualAmountCents' => $actualCents,
                    'varianceCents' => $varianceCents,
                    'variancePercentage' => $variancePercentage,
                    'expenseCount' => $subcategory->expenses->count(),
                ];
            }
        }

        return response()->json($stats);
    }
}
