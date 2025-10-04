<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    public function index(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        $query = $budget->expenses()->with('subcategory.budgetCategory');

        // Filter by subcategory
        if ($request->has('subcatId')) {
            $query->where('budget_subcategory_id', $request->subcatId);
        }

        // Search
        if ($request->has('q')) {
            $query->where('label', 'ILIKE', '%' . $request->q . '%');
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
        ]);

        // Verify subcategory belongs to this budget
        $subcategory = \App\Models\BudgetSubcategory::findOrFail($validated['budget_subcategory_id']);
        if ($subcategory->budgetCategory->budget_id !== $budget->id) {
            return response()->json(['message' => 'Sous-catégorie invalide pour ce budget'], 422);
        }

        $expense = $budget->expenses()->create($validated);

        return response()->json($expense->load('subcategory.budgetCategory'), 201);
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
        ]);

        if (isset($validated['budget_subcategory_id'])) {
            $subcategory = \App\Models\BudgetSubcategory::findOrFail($validated['budget_subcategory_id']);
            if ($subcategory->budgetCategory->budget_id !== $expense->budget_id) {
                return response()->json(['message' => 'Sous-catégorie invalide pour ce budget'], 422);
            }
        }

        $expense->update($validated);

        return response()->json($expense->load('subcategory.budgetCategory'));
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense->budget);

        $expense->delete();

        return response()->json(null, 204);
    }

    public function importCsv(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($csvData);

        $imported = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                $data = array_combine($header, $row);

                // Find subcategory by name
                $subcategory = $budget->categories()
                    ->whereHas('subcategories', function ($q) use ($data) {
                        $q->where('name', $data['subcategory'] ?? '');
                    })
                    ->first()
                    ?->subcategories()
                    ->where('name', $data['subcategory'] ?? '')
                    ->first();

                if (!$subcategory) {
                    $errors[] = "Ligne " . ($index + 2) . ": Sous-catégorie '{$data['subcategory']}' non trouvée";
                    continue;
                }

                $budget->expenses()->create([
                    'budget_subcategory_id' => $subcategory->id,
                    'date' => $data['date'] ?? now(),
                    'label' => $data['label'] ?? 'Import CSV',
                    'amount_cents' => (int) ($data['amount_cents'] ?? 0),
                    'payment_method' => $data['payment_method'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Ligne " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'imported' => $imported,
            'errors' => $errors,
        ]);
    }

    public function exportCsv(Request $request, Budget $budget)
    {
        $this->authorize('view', $budget);

        $expenses = $budget->expenses()->with('subcategory.budgetCategory')->get();

        $csv = "date,label,amount_cents,category,subcategory,payment_method,notes\n";

        foreach ($expenses as $expense) {
            $csv .= implode(',', [
                $expense->date->format('Y-m-d'),
                '"' . str_replace('"', '""', $expense->label) . '"',
                $expense->amount_cents,
                '"' . str_replace('"', '""', $expense->subcategory->budgetCategory->name) . '"',
                '"' . str_replace('"', '""', $expense->subcategory->name) . '"',
                '"' . str_replace('"', '""', $expense->payment_method ?? '') . '"',
                '"' . str_replace('"', '""', $expense->notes ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="expenses-' . $budget->month->format('Y-m') . '.csv"');
    }
}
