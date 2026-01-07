<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->assets();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $allAssets = $query->orderBy('updated_at', 'desc')->get();

        // Separate assets and liabilities
        $assets = $allAssets->where('is_liability', false);
        $liabilities = $allAssets->where('is_liability', true);

        $totalAssetsCents = $assets->sum('value_cents');
        $totalLiabilitiesCents = $liabilities->sum('value_cents');
        $netWorthCents = $totalAssetsCents - $totalLiabilitiesCents;

        return response()->json([
            'assets' => $assets->values(),
            'liabilities' => $liabilities->values(),
            'totalAssetsCents' => $totalAssetsCents,
            'totalLiabilitiesCents' => $totalLiabilitiesCents,
            'netWorthCents' => $netWorthCents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'is_liability' => 'sometimes|boolean',
            'label' => 'required|string|max:255',
            'institution' => 'nullable|string|max:255',
            'value_cents' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $asset = $request->user()->assets()->create($validated);

        return response()->json($asset, 201);
    }

    public function types(Request $request)
    {
        // Get distinct types used by the user
        $types = $request->user()->assets()
            ->distinct()
            ->pluck('type')
            ->sort()
            ->values()
            ->toArray();

        return response()->json(['types' => $types]);
    }

    public function show(Request $request, Asset $asset)
    {
        $this->authorize('view', $asset);

        return response()->json($asset);
    }

    public function update(Request $request, Asset $asset)
    {
        $this->authorize('update', $asset);

        $validated = $request->validate([
            'type' => 'sometimes|string|max:100',
            'is_liability' => 'sometimes|boolean',
            'label' => 'sometimes|string|max:255',
            'institution' => 'nullable|string|max:255',
            'value_cents' => 'sometimes|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $asset->update($validated);

        return response()->json($asset);
    }

    public function destroy(Request $request, Asset $asset)
    {
        $this->authorize('delete', $asset);

        $asset->delete();

        return response()->json(null, 204);
    }
}
