<?php

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BudgetSubcategory;
use App\Models\Expense;
use App\Models\User;

test('budget calculates total planned correctly', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create(['planned_amount_cents' => 10000]);

    expect($budget->fresh()->total_planned_cents)->toBe(10000);
});

test('budget calculates variance correctly', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create(['planned_amount_cents' => 10000]);
    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create();
    Expense::factory()->for($budget)->for($subcategory, 'subcategory')->create(['amount_cents' => 8000]);

    $budget = $budget->fresh();
    expect($budget->total_planned_cents)->toBe(10000);
    expect($budget->total_actual_cents)->toBe(8000);
    expect($budget->variance_cents)->toBe(-2000);
});

test('subcategory calculates variance percentage correctly', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create();
    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create(['planned_amount_cents' => 10000]);
    Expense::factory()->for($budget)->for($subcategory, 'subcategory')->create(['amount_cents' => 12000]);

    $subcategory = $subcategory->fresh();
    expect($subcategory->actual_amount_cents)->toBe(12000);
    expect($subcategory->variance_cents)->toBe(2000);
    expect(round($subcategory->variance_percentage, 1))->toBe(20.0);
});
