<?php

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BudgetSubcategory;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;

test('user can create expense', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create();
    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/budgets/{$budget->id}/expenses", [
            'budget_subcategory_id' => $subcategory->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'label' => 'Test Expense',
            'amount_cents' => 5000,
            'payment_method' => 'CB',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id', 'label', 'amountCents', 'budgetSubcategory',
        ]);

    expect(Expense::where('budget_id', $budget->id)->exists())->toBeTrue();
});

test('user can list expenses', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create();
    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create();
    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->count(3)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/expenses");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('user can update expense', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create();
    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create();
    $expense = Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/expenses/{$expense->id}", [
            'label' => 'Updated Label',
            'amount_cents' => 7000,
        ]);

    $response->assertStatus(200);
    expect($expense->fresh()->label)->toBe('Updated Label');
});

test('user can delete expense', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create();
    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create();
    $expense = Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/expenses/{$expense->id}");

    $response->assertStatus(204);
    expect(Expense::find($expense->id))->toBeNull();
});
