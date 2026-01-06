<?php

namespace App\Http\Controllers;

use App\Models\BudgetTemplate;
use Illuminate\Http\Request;

class BudgetTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = $request->user()
            ->budgetTemplates()
            ->with('categories.subcategories')
            ->get();

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'revenue_cents' => 'nullable|integer|min:0',
            'categories' => 'array',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.planned_amount_cents' => 'required|integer|min:0',
            'categories.*.sort_order' => 'integer|min:0',
            'categories.*.subcategories' => 'array',
            'categories.*.subcategories.*.name' => 'required|string|max:255',
            'categories.*.subcategories.*.planned_amount_cents' => 'required|integer|min:0',
            'categories.*.subcategories.*.sort_order' => 'integer|min:0',
        ]);

        $template = $request->user()->budgetTemplates()->create([
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
            'revenue_cents' => $validated['revenue_cents'] ?? null,
        ]);

        if (isset($validated['categories'])) {
            foreach ($validated['categories'] as $catData) {
                $category = $template->categories()->create([
                    'name' => $catData['name'],
                    'planned_amount_cents' => $catData['planned_amount_cents'],
                    'sort_order' => $catData['sort_order'] ?? 0,
                ]);

                if (isset($catData['subcategories'])) {
                    foreach ($catData['subcategories'] as $subData) {
                        $category->subcategories()->create([
                            'name' => $subData['name'],
                            'planned_amount_cents' => $subData['planned_amount_cents'],
                            'sort_order' => $subData['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        }

        return response()->json($template->load('categories.subcategories'), 201);
    }

    public function show(Request $request, BudgetTemplate $template)
    {
        $this->authorize('view', $template);

        return response()->json($template->load('categories.subcategories'));
    }

    public function update(Request $request, BudgetTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_default' => 'sometimes|boolean',
            'revenue_cents' => 'sometimes|nullable|integer|min:0',
            'categories' => 'sometimes|array',
            'categories.*.id' => 'sometimes|exists:template_categories,id',
            'categories.*.name' => 'required_with:categories|string|max:255',
            'categories.*.planned_amount_cents' => 'required_with:categories|integer|min:0',
            'categories.*.sort_order' => 'sometimes|integer|min:0',
            'categories.*.subcategories' => 'sometimes|array',
            'categories.*.subcategories.*.id' => 'sometimes|exists:template_subcategories,id',
            'categories.*.subcategories.*.name' => 'required_with:categories.*.subcategories|string|max:255',
            'categories.*.subcategories.*.planned_amount_cents' => 'required_with:categories.*.subcategories|integer|min:0',
            'categories.*.subcategories.*.sort_order' => 'sometimes|integer|min:0',
        ]);

        // Mettre à jour le template lui-même
        $template->update([
            'name' => $validated['name'] ?? $template->name,
            'is_default' => $validated['is_default'] ?? $template->is_default,
            'revenue_cents' => $validated['revenue_cents'] ?? $template->revenue_cents,
        ]);

        // Si des catégories sont fournies, les gérer
        if (isset($validated['categories'])) {
            $existingCategoryIds = [];

            foreach ($validated['categories'] as $index => $catData) {
                $existingSubcategoryIds = []; // Reset pour chaque catégorie

                if (isset($catData['id'])) {
                    // Mise à jour d'une catégorie existante
                    $category = $template->categories()->findOrFail($catData['id']);
                    $category->update([
                        'name' => $catData['name'],
                        'planned_amount_cents' => $catData['planned_amount_cents'],
                        'sort_order' => $catData['sort_order'] ?? $index,
                    ]);
                    $existingCategoryIds[] = $category->id;
                } else {
                    // Création d'une nouvelle catégorie
                    $category = $template->categories()->create([
                        'name' => $catData['name'],
                        'planned_amount_cents' => $catData['planned_amount_cents'],
                        'sort_order' => $catData['sort_order'] ?? $index,
                    ]);
                    $existingCategoryIds[] = $category->id;
                }

                // Gérer les sous-catégories
                if (isset($catData['subcategories'])) {
                    foreach ($catData['subcategories'] as $subIndex => $subData) {
                        if (isset($subData['id'])) {
                            // Mise à jour
                            $subcategory = $category->subcategories()->findOrFail($subData['id']);
                            $subcategory->update([
                                'name' => $subData['name'],
                                'planned_amount_cents' => $subData['planned_amount_cents'],
                                'sort_order' => $subData['sort_order'] ?? $subIndex,
                            ]);
                            $existingSubcategoryIds[] = $subcategory->id;
                        } else {
                            // Création
                            $subcategory = $category->subcategories()->create([
                                'name' => $subData['name'],
                                'planned_amount_cents' => $subData['planned_amount_cents'],
                                'sort_order' => $subData['sort_order'] ?? $subIndex,
                            ]);
                            $existingSubcategoryIds[] = $subcategory->id;
                        }
                    }
                }

                // Supprimer les sous-catégories qui ne sont plus dans la liste (pour cette catégorie)
                $category->subcategories()->whereNotIn('id', $existingSubcategoryIds)->delete();
            }

            // Supprimer les catégories qui ne sont plus dans la liste
            $template->categories()->whereNotIn('id', $existingCategoryIds)->delete();
        }

        return response()->json($template->load('categories.subcategories'));
    }

    public function destroy(Request $request, BudgetTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->json(null, 204);
    }

    public function setDefault(Request $request, BudgetTemplate $template)
    {
        $this->authorize('update', $template);

        // Désactiver tous les templates par défaut de l'utilisateur
        $request->user()->budgetTemplates()->update(['is_default' => false]);

        // Activer celui-ci comme défaut
        $template->update(['is_default' => true]);

        return response()->json($template);
    }
}
