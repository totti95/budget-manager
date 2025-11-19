<?php

namespace App\Http\Controllers;

use App\Models\WealthHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WealthHistoryController extends Controller
{
    /**
     * Get wealth history for the authenticated user
     */
    public function index(Request $request)
    {
        $query = WealthHistory::where('user_id', $request->user()->id)
            ->orderBy('recorded_at', 'desc');

        if ($request->has('from')) {
            $query->where('recorded_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('recorded_at', '<=', $request->to);
        }

        $history = $query->get();

        return response()->json($history);
    }

    /**
     * Record current wealth snapshot (typically at end of month)
     */
    public function record(Request $request)
    {
        $validated = $request->validate([
            'recorded_at' => 'required|date',
        ]);

        $recordDate = Carbon::parse($validated['recorded_at']);

        // Calculate current assets and liabilities
        $totalAssets = $request->user()->assets()
            ->where('is_liability', false)
            ->sum('value_cents');

        $totalLiabilities = $request->user()->assets()
            ->where('is_liability', true)
            ->sum('value_cents');

        $netWorth = $totalAssets - $totalLiabilities;

        // Create or update entry for this date
        $history = WealthHistory::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'recorded_at' => $recordDate,
            ],
            [
                'total_assets_cents' => $totalAssets,
                'total_liabilities_cents' => $totalLiabilities,
                'net_worth_cents' => $netWorth,
            ]
        );

        return response()->json($history, 201);
    }

    /**
     * Delete a wealth history entry
     */
    public function destroy(Request $request, WealthHistory $wealthHistory)
    {
        // Ensure user can only delete their own entries
        if ($wealthHistory->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $wealthHistory->delete();

        return response()->json(null, 204);
    }
}
