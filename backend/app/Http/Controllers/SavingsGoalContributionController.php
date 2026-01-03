<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use App\Models\SavingsGoalContribution;
use App\Services\SavingsGoalService;
use Illuminate\Http\Request;

class SavingsGoalContributionController extends Controller
{
    /**
     * Display a listing of contributions for a savings goal.
     */
    public function index(SavingsGoal $savingsGoal)
    {
        $this->authorize('view', $savingsGoal);

        $contributions = $savingsGoal->contributions()
            ->orderBy('contribution_date', 'desc')
            ->get();

        return response()->json($contributions);
    }

    /**
     * Store a newly created contribution.
     */
    public function store(Request $request, SavingsGoal $savingsGoal, SavingsGoalService $service)
    {
        $this->authorize('update', $savingsGoal);

        $validated = $request->validate([
            'amount_cents' => 'required|integer|min:1',
            'contribution_date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $contribution = SavingsGoalContribution::create(array_merge($validated, [
            'savings_goal_id' => $savingsGoal->id,
            'user_id' => $request->user()->id,
        ]));

        // Mettre à jour le montant actuel de l'objectif
        $oldAmount = $savingsGoal->current_amount_cents;
        $savingsGoal->current_amount_cents += $validated['amount_cents'];
        $savingsGoal->save();

        // Vérifier les jalons
        $service->checkMilestones($savingsGoal, $oldAmount);

        return response()->json($contribution, 201);
    }

    /**
     * Remove the specified contribution.
     */
    public function destroy(SavingsGoal $savingsGoal, SavingsGoalContribution $contribution)
    {
        $this->authorize('update', $savingsGoal);

        if ($contribution->savings_goal_id !== $savingsGoal->id) {
            return response()->json(['message' => 'Contribution not found'], 404);
        }

        // Soustraire le montant de l'objectif
        $savingsGoal->current_amount_cents -= $contribution->amount_cents;
        $savingsGoal->save();

        $contribution->delete();

        return response()->json(null, 204);
    }
}
