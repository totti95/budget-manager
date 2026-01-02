<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = $request->user()->tags()
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/|size:7',
        ]);

        // Check uniqueness manually for better error message
        $existingTag = $request->user()->tags()
            ->where('name', $validated['name'])
            ->first();

        if ($existingTag) {
            throw ValidationException::withMessages([
                'name' => ['Un tag avec ce nom existe déjà.'],
            ]);
        }

        // Set default color if not provided
        if (!isset($validated['color'])) {
            $validated['color'] = '#3B82F6';
        }

        $tag = $request->user()->tags()->create($validated);

        return response()->json($tag, 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $this->authorize('update', $tag);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'color' => 'sometimes|nullable|string|regex:/^#[0-9A-Fa-f]{6}$/|size:7',
        ]);

        // Check uniqueness if name is being changed
        if (isset($validated['name']) && $validated['name'] !== $tag->name) {
            $existingTag = $request->user()->tags()
                ->where('name', $validated['name'])
                ->where('id', '!=', $tag->id)
                ->first();

            if ($existingTag) {
                throw ValidationException::withMessages([
                    'name' => ['Un tag avec ce nom existe déjà.'],
                ]);
            }
        }

        $tag->update($validated);

        return response()->json($tag);
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->noContent();
    }
}
