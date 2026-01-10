<?php

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BudgetSubcategory;
use App\Models\Expense;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    // Create roles if they don't exist
    if (! Role::where('label', 'user')->exists()) {
        Role::create(['label' => 'user']);
    }
    if (! Role::where('label', 'admin')->exists()) {
        Role::create(['label' => 'admin']);
    }
});

test('user can get budget summary stats', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    $category = BudgetCategory::factory()->for($budget)->create([
        'planned_amount_cents' => 100000,
    ]);

    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create([
        'planned_amount_cents' => 100000,
    ]);

    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create([
        'amount_cents' => 75000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/summary");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'totalPlannedCents',
            'totalActualCents',
            'varianceCents',
            'variancePercentage',
            'expenseCount',
        ])
        ->assertJson([
            'totalPlannedCents' => 100000,
            'totalActualCents' => 75000,
            'varianceCents' => -25000,
            'expenseCount' => 1,
        ]);
});

test('user can get stats by category', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    $category = BudgetCategory::factory()->for($budget)->create([
        'name' => 'Alimentation',
        'planned_amount_cents' => 50000,
    ]);

    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create([
        'planned_amount_cents' => 50000,
    ]);

    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create([
        'amount_cents' => 30000,
    ]);
    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create([
        'amount_cents' => 20000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/by-category");

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'plannedAmountCents',
                'actualAmountCents',
                'varianceCents',
                'variancePercentage',
                'expenseCount',
            ],
        ]);

    // Vérifier que les calculs sont corrects
    $categoryStats = collect($response->json())->firstWhere('name', 'Alimentation');
    expect($categoryStats)->not->toBeNull();
    expect($categoryStats['plannedAmountCents'])->toBe(50000);
    expect($categoryStats['actualAmountCents'])->toBe(50000); // 30000 + 20000
    expect($categoryStats['varianceCents'])->toBe(0);
    expect($categoryStats['expenseCount'])->toBe(2);
});

test('user can get stats by subcategory', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    $category = BudgetCategory::factory()->for($budget)->create();

    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create([
        'name' => 'Restaurant',
        'planned_amount_cents' => 15000,
    ]);

    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create([
        'amount_cents' => 20000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/by-subcategory");

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'categoryName',
                'plannedAmountCents',
                'actualAmountCents',
                'varianceCents',
                'variancePercentage',
                'expenseCount',
            ],
        ]);

    // Vérifier le dépassement
    $subcategoryStats = collect($response->json())->firstWhere('name', 'Restaurant');
    expect($subcategoryStats)->not->toBeNull();
    expect($subcategoryStats['plannedAmountCents'])->toBe(15000);
    expect($subcategoryStats['actualAmountCents'])->toBe(20000);
    expect($subcategoryStats['varianceCents'])->toBe(5000); // Dépassement de 5000
});

test('variance percentage is calculated correctly for positive variance', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    $category = BudgetCategory::factory()->for($budget)->create([
        'planned_amount_cents' => 100000,
    ]);

    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create([
        'planned_amount_cents' => 100000,
    ]);

    // Dépensé 120000 au lieu de 100000 = +20% de dépassement
    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create([
        'amount_cents' => 120000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/summary");

    $response->assertStatus(200);

    expect($response->json('varianceCents'))->toBe(20000);
    expect(round($response->json('variancePercentage'), 1))->toBe(20.0);
});

test('variance percentage is calculated correctly for negative variance', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    $category = BudgetCategory::factory()->for($budget)->create([
        'planned_amount_cents' => 100000,
    ]);

    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create([
        'planned_amount_cents' => 100000,
    ]);

    // Dépensé 80000 au lieu de 100000 = -20% d'économie
    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create([
        'amount_cents' => 80000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/summary");

    $response->assertStatus(200);

    expect($response->json('varianceCents'))->toBe(-20000);
    expect(round($response->json('variancePercentage'), 1))->toBe(-20.0);
});

test('user can get top categories by spending', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    // Catégorie 1 : 50000 dépensés
    $category1 = BudgetCategory::factory()->for($budget)->create(['name' => 'Catégorie A']);
    $subcategory1 = BudgetSubcategory::factory()->for($category1, 'budgetCategory')->create();
    Expense::factory()->for($budget)->for($subcategory1, 'budgetSubcategory')->create(['amount_cents' => 50000]);

    // Catégorie 2 : 30000 dépensés
    $category2 = BudgetCategory::factory()->for($budget)->create(['name' => 'Catégorie B']);
    $subcategory2 = BudgetSubcategory::factory()->for($category2, 'budgetCategory')->create();
    Expense::factory()->for($budget)->for($subcategory2, 'budgetSubcategory')->create(['amount_cents' => 30000]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/top-categories?limit=5");

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['name', 'actualCents'],
        ]);

    // Vérifier que le tri est correct (par montant décroissant)
    $categories = collect($response->json());
    expect($categories->first()['name'])->toBe('Catégorie A');
    expect($categories->first()['actualCents'])->toBe(50000);
});

test('stats return empty arrays when no expenses exist', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    BudgetCategory::factory()->for($budget)->create([
        'planned_amount_cents' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/summary");

    $response->assertStatus(200)
        ->assertJson([
            'totalPlannedCents' => 0,
            'totalActualCents' => 0,
            'varianceCents' => 0,
            'expenseCount' => 0,
        ]);
});

test('user cannot view stats for another users budget', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $budget = Budget::factory()->for($user2)->create();

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/summary");

    $response->assertStatus(403);
});

test('stats handle multiple expenses in same subcategory correctly', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();
    $category = BudgetCategory::factory()->for($budget)->create([
        'planned_amount_cents' => 100000,
    ]);
    $subcategory = BudgetSubcategory::factory()->for($category, 'budgetCategory')->create([
        'planned_amount_cents' => 100000,
    ]);

    // Ajouter plusieurs dépenses
    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create(['amount_cents' => 25000]);
    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create(['amount_cents' => 30000]);
    Expense::factory()->for($budget)->for($subcategory, 'budgetSubcategory')->create(['amount_cents' => 45000]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}/stats/summary");

    $response->assertStatus(200)
        ->assertJson([
            'totalActualCents' => 100000, // 25000 + 30000 + 45000
            'expenseCount' => 3,
        ]);
});
