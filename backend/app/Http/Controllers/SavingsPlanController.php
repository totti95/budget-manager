<?php

namespace App\Http\Controllers;

use App\Models\SavingsPlan;
use Illuminate\Http\Request;

class SavingsPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->savingsPlans();

        if ($request->has('month')) {
            $query->whereDate('month', $request->month);
        }

        $plans = $query->orderBy('month', 'desc')->get();

        // Add actual savings to each plan
        $plans->each(function ($plan) {
            $plan->actual_cents = $plan->getActualCentsAttribute();
        });

        return response()->json($plans);
    }

    public function show(Request $request, SavingsPlan $savingsPlan)
    {
        $this->authorize('view', $savingsPlan);

        $savingsPlan->actual_cents = $savingsPlan->getActualCentsAttribute();

        return response()->json($savingsPlan);
    }

    public function update(Request $request, SavingsPlan $savingsPlan)
    {
        $this->authorize('update', $savingsPlan);

        $validated = $request->validate([
            'planned_cents' => 'required|integer|min:0',
        ]);

        $savingsPlan->update($validated);

        return response()->json($savingsPlan);
    }
}
