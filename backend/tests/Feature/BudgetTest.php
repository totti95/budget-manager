<?php

use App\Models\Budget;
use App\Models\BudgetTemplate;
use App\Models\User;
use Carbon\Carbon;

test('user can list their budgets', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/budgets');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'month', 'user_id'],
            ],
        ]);
});

test('user can generate budget from default template', function () {
    $user = User::factory()->create();
    $template = BudgetTemplate::factory()->for($user)->create([
        'is_default' => true,
    ]);

    $month = Carbon::now()->format('Y-m');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/budgets/generate', [
            'month' => $month,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id', 'name', 'month', 'categories',
        ]);

    expect(Budget::where('user_id', $user->id)->where('month', $month . '-01')->exists())->toBeTrue();
});

test('user can view budget details', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}");

    $response->assertStatus(200)
        ->assertJson([
            'id' => $budget->id,
            'name' => $budget->name,
        ]);
});

test('user cannot view another users budget', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $budget = Budget::factory()->for($user2)->create();

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/budgets/{$budget->id}");

    $response->assertStatus(403);
});
