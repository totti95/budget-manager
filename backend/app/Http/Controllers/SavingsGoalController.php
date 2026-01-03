<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\SavingsGoal;
use App\Services\SavingsGoalService;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    /**
     * Display a listing of savings goals for the authenticated user.
     */
    public function index(Request $request)
    {
        $goals = SavingsGoal::where('user_id', $request->user()->id)
            ->with(['asset', 'contributions'])
            ->orderBy('priority', 'desc')
            ->orderBy('target_date')
            ->get()
            ->map(function ($goal) {
                return array_merge($goal->toArray(), [
                    'progress_percentage' => $goal->progress_percentage,
                    'days_remaining' => $goal->days_remaining,
                    'time_progress_percentage' => $goal->time_progress_percentage,
                    'is_on_track' => $goal->is_on_track,
                ]);
            });

        return response()->json($goals);
    }

    /**
     * Store a newly created savings goal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'nullable|exists:assets,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_amount_cents' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'target_date' => 'nullable|date|after:start_date',
            'priority' => 'integer|min:0|max:100',
            'notify_milestones' => 'boolean',
            'notify_risk' => 'boolean',
            'notify_reminder' => 'boolean',
            'reminder_day_of_month' => 'nullable|integer|min:1|max:28',
            'suggested_monthly_amount_cents' => 'nullable|integer|min:0',
        ]);

        // Vérifier que l'asset appartient à l'utilisateur
        if (isset($validated['asset_id']) && $validated['asset_id']) {
            $asset = Asset::findOrFail($validated['asset_id']);
            $this->authorize('view', $asset);
        }

        $goal = SavingsGoal::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));

        // Si lié à un actif, initialiser current_amount_cents avec la valeur de l'actif
        if ($goal->asset_id) {
            $goal->current_amount_cents = $goal->asset->value_cents;
            $goal->save();
        }

        return response()->json($goal->load('asset'), 201);
    }

    /**
     * Display the specified savings goal.
     */
    public function show(Request $request, SavingsGoal $savingsGoal)
    {
        $this->authorize('view', $savingsGoal);

        $savingsGoal->load(['asset', 'contributions']);

        return response()->json(array_merge($savingsGoal->toArray(), [
            'progress_percentage' => $savingsGoal->progress_percentage,
            'days_remaining' => $savingsGoal->days_remaining,
            'time_progress_percentage' => $savingsGoal->time_progress_percentage,
            'is_on_track' => $savingsGoal->is_on_track,
        ]));
    }

    /**
     * Update the specified savings goal.
     */
    public function update(Request $request, SavingsGoal $savingsGoal)
    {
        $this->authorize('update', $savingsGoal);

        $validated = $request->validate([
            'asset_id' => 'nullable|exists:assets,id',
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'target_amount_cents' => 'integer|min:1',
            'start_date' => 'date',
            'target_date' => 'nullable|date|after:start_date',
            'status' => 'in:active,completed,abandoned,paused',
            'priority' => 'integer|min:0|max:100',
            'notify_milestones' => 'boolean',
            'notify_risk' => 'boolean',
            'notify_reminder' => 'boolean',
            'reminder_day_of_month' => 'nullable|integer|min:1|max:28',
            'suggested_monthly_amount_cents' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['asset_id']) && $validated['asset_id']) {
            $asset = Asset::findOrFail($validated['asset_id']);
            $this->authorize('view', $asset);
        }

        $savingsGoal->update($validated);

        return response()->json($savingsGoal->load('asset'));
    }

    /**
     * Remove the specified savings goal.
     */
    public function destroy(SavingsGoal $savingsGoal)
    {
        $this->authorize('delete', $savingsGoal);
        $savingsGoal->delete();
        return response()->json(null, 204);
    }

    /**
     * Synchronise current_amount_cents avec la valeur de l'actif lié
     */
    public function syncAsset(SavingsGoal $savingsGoal, SavingsGoalService $service)
    {
        $this->authorize('update', $savingsGoal);

        if (!$savingsGoal->asset_id) {
            return response()->json(['message' => 'No asset linked'], 400);
        }

        $oldAmount = $savingsGoal->current_amount_cents;
        $savingsGoal->current_amount_cents = $savingsGoal->asset->value_cents;
        $savingsGoal->save();

        // Vérifier si objectif atteint
        $service->checkMilestones($savingsGoal, $oldAmount);

        return response()->json($savingsGoal);
    }
}
