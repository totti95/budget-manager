<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;

class BudgetComparisonService
{
    /**
     * Compare multiple budgets across different months
     *
     * @param User $user
     * @param array $months Array of month strings (Y-m format)
     *
     * @return array
     */
    public function compare(User $user, array $months): array
    {
        $budgets = $this->loadBudgetsWithStats($user, $months);
        $evolution = $this->calculateEvolution($budgets);

        return [
            'budgets' => $budgets,
            'comparison' => [
                'evolution' => $evolution,
            ],
        ];
    }

    /**
     * Load budgets for given months with calculated stats
     *
     * @param User $user
     * @param array $months
     *
     * @return array
     */
    private function loadBudgetsWithStats(User $user, array $months): array
    {
        $budgets = [];

        foreach ($months as $month) {
            $monthDate = Carbon::parse($month . '-01');
            $budget = $user->budgets()
                ->where('month', $monthDate)
                ->with(['categories.subcategories.expenses'])
                ->first();

            if ($budget) {
                $budget->stats = $this->calculateBudgetStats($budget);
                $budgets[] = $budget;
            }
        }

        return $budgets;
    }

    /**
     * Calculate statistics for a single budget
     *
     * @param Budget $budget
     *
     * @return array
     */
    private function calculateBudgetStats(Budget $budget): array
    {
        $totalPlanned = 0;
        $totalActual = 0;
        $byCategory = [];

        foreach ($budget->categories as $category) {
            $categoryStats = $this->calculateCategoryStats($category);

            $totalPlanned += $categoryStats['planned_cents'];
            $totalActual += $categoryStats['actual_cents'];

            $byCategory[] = [
                'name' => $category->name,
                'planned_cents' => $categoryStats['planned_cents'],
                'actual_cents' => $categoryStats['actual_cents'],
                'variance_cents' => $categoryStats['variance_cents'],
                'variance_percent' => $categoryStats['variance_percent'],
            ];
        }

        return [
            'total_planned_cents' => $totalPlanned,
            'total_actual_cents' => $totalActual,
            'variance_cents' => $totalActual - $totalPlanned,
            'variance_percent' => $this->calculateVariancePercent($totalActual, $totalPlanned),
            'by_category' => $byCategory,
        ];
    }

    /**
     * Calculate statistics for a single category
     *
     * @param \App\Models\BudgetCategory $category
     *
     * @return array
     */
    private function calculateCategoryStats($category): array
    {
        $categoryPlanned = $category->planned_amount_cents;
        $categoryActual = 0;

        foreach ($category->subcategories as $subcategory) {
            $categoryActual += $subcategory->expenses->sum('amount_cents');
        }

        return [
            'name' => $category->name,
            'planned_cents' => $categoryPlanned,
            'actual_cents' => $categoryActual,
            'variance_cents' => $categoryActual - $categoryPlanned,
            'variance_percent' => $this->calculateVariancePercent($categoryActual, $categoryPlanned),
        ];
    }

    /**
     * Calculate variance percentage
     *
     * @param int $actual
     * @param int $planned
     *
     * @return float
     */
    private function calculateVariancePercent(int $actual, int $planned): float
    {
        if ($planned <= 0) {
            return 0;
        }

        return round((($actual - $planned) / $planned) * 100, 2);
    }

    /**
     * Calculate evolution across budgets
     *
     * @param array $budgets
     *
     * @return array
     */
    private function calculateEvolution(array $budgets): array
    {
        $categoryData = $this->extractCategoryData($budgets);
        $evolution = [];

        foreach ($categoryData as $categoryName => $values) {
            if (count($values) >= 2) {
                $evolution[] = [
                    'category_name' => $categoryName,
                    'values' => $values,
                    'evolution_percent' => $this->calculateEvolutionPercent($values),
                ];
            }
        }

        return $evolution;
    }

    /**
     * Extract category data from budgets for evolution calculation
     *
     * @param array $budgets
     *
     * @return array
     */
    private function extractCategoryData(array $budgets): array
    {
        $categoryData = [];

        foreach ($budgets as $budget) {
            foreach ($budget->stats['by_category'] as $categoryStats) {
                $categoryName = $categoryStats['name'];

                if (! isset($categoryData[$categoryName])) {
                    $categoryData[$categoryName] = [];
                }

                $categoryData[$categoryName][] = $categoryStats['actual_cents'];
            }
        }

        return $categoryData;
    }

    /**
     * Calculate evolution percentage
     *
     * @param array $values
     *
     * @return float
     */
    private function calculateEvolutionPercent(array $values): float
    {
        $firstValue = $values[0];
        $lastValue = $values[count($values) - 1];

        if ($firstValue <= 0) {
            return 0;
        }

        return round((($lastValue - $firstValue) / $firstValue) * 100, 2);
    }
}
