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

        $assets = $query->orderBy('updated_at', 'desc')->get();

        $total = $assets->sum('value_cents');

        return response()->json([
            'assets' => $assets,
            'totalValueCents' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
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
            'type' => 'sometimes|string|max:50',
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
