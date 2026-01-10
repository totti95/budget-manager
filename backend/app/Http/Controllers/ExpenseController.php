<?php

namespace App\Http\Controllers;

use App\Contracts\ExpenseRepositoryInterface;
use App\Models\Budget;
use App\Models\Expense;
use App\Services\CsvImportService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected ExpenseRepositoryInterface $expenseRepository;

    public function __construct(ExpenseRepositoryInterface $expenseRepository)
    {
        $this->expenseRepository = $expenseRepository;
    }

    public function index(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        $query = $budget->expenses()->with('budgetSubcategory.budgetCategory', 'tags');

        // Filter by subcategory
        if ($request->has('subcatId')) {
            $query->where('budget_subcategory_id', $request->subcatId);
        }

        // Filter by tag
        if ($request->has('tagId')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tagId);
            });
        }

        // Search (case-insensitive, MySQL compatible)
        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = '%' . str_replace(['%', '_'], ['\%', '\_'], $request->q) . '%';
            $query->whereRaw('LOWER(label) LIKE LOWER(?)', [$searchTerm]);
        }

        // Date range
        if ($request->has('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        $expenses = $query->orderBy('date', 'desc')->paginate(50);

        return response()->json($expenses);
    }

    public function store(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'budget_subcategory_id' => 'required|exists:budget_subcategories,id',
            'date' => 'required|date',
            'label' => 'required|string|max:255',
            'amount_cents' => 'required|integer|min:1',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        // Verify subcategory belongs to this budget
        $subcategory = \App\Models\BudgetSubcategory::findOrFail($validated['budget_subcategory_id']);
        if ($subcategory->budgetCategory->budget_id !== $budget->id) {
            return response()->json(['message' => 'Sous-catégorie invalide pour ce budget'], 422);
        }

        // Verify tags belong to the user and filter
        if (isset($validated['tag_ids'])) {
            $userTags = $request->user()->tags()->whereIn('id', $validated['tag_ids'])->pluck('id')->toArray();
            $validated['tag_ids'] = $userTags;
        }

        // Add budget_id to validated data
        $validated['budget_id'] = $budget->id;

        $expense = $this->expenseRepository->create($validated);

        // Check for budget exceeded notification
        app(NotificationService::class)->checkBudgetExceeded($expense);

        return response()->json($expense, 201);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense->budget);

        $validated = $request->validate([
            'budget_subcategory_id' => 'sometimes|exists:budget_subcategories,id',
            'date' => 'sometimes|date',
            'label' => 'sometimes|string|max:255',
            'amount_cents' => 'sometimes|integer|min:1',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'tag_ids' => 'sometimes|nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        if (isset($validated['budget_subcategory_id'])) {
            $subcategory = \App\Models\BudgetSubcategory::findOrFail($validated['budget_subcategory_id']);
            if ($subcategory->budgetCategory->budget_id !== $expense->budget_id) {
                return response()->json(['message' => 'Sous-catégorie invalide pour ce budget'], 422);
            }
        }

        // Verify tags belong to the user and filter
        if (isset($validated['tag_ids'])) {
            $userTags = $request->user()->tags()->whereIn('id', $validated['tag_ids'])->pluck('id')->toArray();
            $validated['tag_ids'] = $userTags;
        }

        $expense = $this->expenseRepository->update($expense, $validated);

        // Check for budget exceeded notification
        app(NotificationService::class)->checkBudgetExceeded($expense);

        return response()->json($expense);
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense->budget);

        $this->expenseRepository->delete($expense);

        return response()->json(null, 204);
    }

    public function importCsv(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $request->validate([
            'file' => 'required|file',
        ]);

        $file = $request->file('file');
        $csvService = new CsvImportService();

        try {
            // Valider le fichier
            $csvService->validate($file);

            // Parser et valider le contenu
            $result = $csvService->parse($file);
            $csvData = $result['data'];
            $errors = $result['errors'];

            $imported = 0;

            // Précharger toutes les sous-catégories pour éviter N+1
            $budget->loadMissing('categories.subcategories');

            foreach ($csvData as $index => $data) {
                try {
                    // Trouver la sous-catégorie par nom
                    $subcategory = null;
                    foreach ($budget->categories as $category) {
                        $found = $category->subcategories->firstWhere('name', $data['subcategory']);
                        if ($found) {
                            $subcategory = $found;
                            break;
                        }
                    }

                    if (!$subcategory) {
                        $errors[] = 'Ligne ' . ($index + 2) . ": Sous-catégorie '{$data['subcategory']}' non trouvée";
                        continue;
                    }

                    $budget->expenses()->create([
                        'budget_subcategory_id' => $subcategory->id,
                        'date' => $data['date'],
                        'label' => $data['label'],
                        'amount_cents' => $data['amount_cents'],
                        'payment_method' => $data['payment_method'],
                        'notes' => $data['notes'],
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'Ligne ' . ($index + 2) . ': ' . $e->getMessage();
                }
            }

            return response()->json([
                'imported' => $imported,
                'errors' => $errors,
                'total_rows' => count($csvData),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'imported' => 0,
                'errors' => [$e->getMessage()],
                'total_rows' => 0,
            ], 422);
        }
    }

    public function exportCsv(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        $expenses = $budget->expenses()->with('budgetSubcategory.budgetCategory')->get();

        $csv = "date,label,amount_cents,category,subcategory,payment_method,notes\n";

        foreach ($expenses as $expense) {
            $csv .= implode(',', [
                $expense->date->format('Y-m-d'),
                '"' . str_replace('"', '""', $expense->label) . '"',
                $expense->amount_cents,
                '"' . str_replace('"', '""', $expense->budgetSubcategory->budgetCategory->name) . '"',
                '"' . str_replace('"', '""', $expense->budgetSubcategory->name) . '"',
                '"' . str_replace('"', '""', $expense->payment_method ?? '') . '"',
                '"' . str_replace('"', '""', $expense->notes ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="expenses-' . $budget->month->format('Y-m') . '.csv"');
    }
}
