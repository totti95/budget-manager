<?php

namespace App\Http\Controllers;

use App\Models\RecurringExpense;
use Illuminate\Http\Request;

class RecurringExpenseController extends Controller
{
    /**
     * List all recurring expenses for authenticated user
     */
    public function index(Request $request)
    {
        $recurringExpenses = $request->user()
            ->recurringExpenses()
            ->with('templateSubcategory.templateCategory')
            ->orderBy('label')
            ->get();

        return response()->json($recurringExpenses);
    }

    /**
     * Create a new recurring expense
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_subcategory_id' => 'nullable|exists:template_subcategories,id',
            'label' => 'required|string|max:255',
            'amount_cents' => 'required|integer|min:1',
            'frequency' => 'required|in:monthly,weekly,yearly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'auto_create' => 'boolean',
            'is_active' => 'boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // Frequency-specific validation
        $this->validateFrequencyFields($validated);

        $recurringExpense = $request->user()->recurringExpenses()->create($validated);

        return response()->json(
            $recurringExpense->load('templateSubcategory.templateCategory'),
            201
        );
    }

    /**
     * Show a specific recurring expense
     */
    public function show(Request $request, RecurringExpense $recurringExpense)
    {
        $this->authorize('view', $recurringExpense);

        return response()->json(
            $recurringExpense->load('templateSubcategory.templateCategory')
        );
    }

    /**
     * Update a recurring expense
     */
    public function update(Request $request, RecurringExpense $recurringExpense)
    {
        $this->authorize('update', $recurringExpense);

        $validated = $request->validate([
            'template_subcategory_id' => 'sometimes|nullable|exists:template_subcategories,id',
            'label' => 'sometimes|string|max:255',
            'amount_cents' => 'sometimes|integer|min:1',
            'frequency' => 'sometimes|in:monthly,weekly,yearly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'auto_create' => 'boolean',
            'is_active' => 'boolean',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // Merge with existing data for validation
        $merged = array_merge($recurringExpense->toArray(), $validated);
        $this->validateFrequencyFields($merged);

        $recurringExpense->update($validated);

        return response()->json(
            $recurringExpense->load('templateSubcategory.templateCategory')
        );
    }

    /**
     * Delete a recurring expense
     */
    public function destroy(Request $request, RecurringExpense $recurringExpense)
    {
        $this->authorize('delete', $recurringExpense);

        $recurringExpense->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle is_active status
     */
    public function toggleActive(Request $request, RecurringExpense $recurringExpense)
    {
        $this->authorize('update', $recurringExpense);

        $recurringExpense->update([
            'is_active' => ! $recurringExpense->is_active,
        ]);

        return response()->json($recurringExpense);
    }

    /**
     * Validate frequency-specific fields
     */
    private function validateFrequencyFields(array $data): void
    {
        $frequency = $data['frequency'];

        if ($frequency === 'monthly') {
            if (empty($data['day_of_month'])) {
                abort(422, 'day_of_month est requis pour la fréquence mensuelle');
            }
        }

        if ($frequency === 'weekly') {
            if (empty($data['day_of_week'])) {
                abort(422, 'day_of_week est requis pour la fréquence hebdomadaire');
            }
        }

        if ($frequency === 'yearly') {
            if (empty($data['month_of_year'])) {
                abort(422, 'month_of_year est requis pour la fréquence annuelle');
            }
        }
    }
}
