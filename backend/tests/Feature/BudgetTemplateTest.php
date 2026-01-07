<?php

use App\Models\BudgetTemplate;
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

test('user can list their budget templates', function () {
    $user = User::factory()->create();
    $template = BudgetTemplate::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/templates');

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['id', 'name', 'isDefault', 'userId'],
        ]);
});

test('user can create a budget template', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/templates', [
            'name' => 'Mon Template Test',
            'isDefault' => false,
            'revenueCents' => 250000,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id', 'name', 'isDefault', 'userId',
        ])
        ->assertJson([
            'name' => 'Mon Template Test',
            'isDefault' => false,
        ]);

    expect(BudgetTemplate::where('user_id', $user->id)->where('name', 'Mon Template Test')->exists())->toBeTrue();
});

test('user can create a template with categories and subcategories', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/templates', [
            'name' => 'Template Complet',
            'isDefault' => true,
            'categories' => [
                [
                    'name' => 'Logement',
                    'plannedAmountCents' => 100000,
                    'subcategories' => [
                        [
                            'name' => 'Loyer',
                            'plannedAmountCents' => 80000,
                        ],
                        [
                            'name' => 'Charges',
                            'plannedAmountCents' => 20000,
                        ],
                    ],
                ],
            ],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'name',
            'isDefault',
            'categories' => [
                '*' => [
                    'id',
                    'name',
                    'plannedAmountCents',
                    'subcategories' => [
                        '*' => ['id', 'name', 'plannedAmountCents'],
                    ],
                ],
            ],
        ]);

    $template = BudgetTemplate::where('user_id', $user->id)
        ->where('name', 'Template Complet')
        ->with('categories.subcategories')
        ->first();

    expect($template)->not->toBeNull();
    expect($template->categories)->toHaveCount(1);
    expect($template->categories[0]->subcategories)->toHaveCount(2);
});

test('user can update a budget template', function () {
    $user = User::factory()->create();
    $template = BudgetTemplate::factory()->for($user)->create([
        'name' => 'Template Original',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/templates/{$template->id}", [
            'name' => 'Template Modifié',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'name' => 'Template Modifié',
        ]);
});

test('user can delete their budget template', function () {
    $user = User::factory()->create();
    $template = BudgetTemplate::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/templates/{$template->id}");

    $response->assertStatus(204);

    expect(BudgetTemplate::find($template->id))->toBeNull();
});

test('user can set a template as default', function () {
    $user = User::factory()->create();
    $oldDefault = BudgetTemplate::factory()->for($user)->create([
        'is_default' => true,
    ]);
    $newTemplate = BudgetTemplate::factory()->for($user)->create([
        'is_default' => false,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/templates/{$newTemplate->id}/set-default");

    $response->assertStatus(200)
        ->assertJson([
            'isDefault' => true,
        ]);

    // Vérifier que l'ancien template n'est plus par défaut
    $oldDefault->refresh();
    expect($oldDefault->is_default)->toBeFalse();

    // Vérifier que le nouveau est par défaut
    $newTemplate->refresh();
    expect($newTemplate->is_default)->toBeTrue();
});

test('user cannot view another users template', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $template = BudgetTemplate::factory()->for($user2)->create();

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/templates/{$template->id}");

    $response->assertStatus(403);
});

test('user cannot delete another users template', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $template = BudgetTemplate::factory()->for($user2)->create();

    $response = $this->actingAs($user1, 'sanctum')
        ->deleteJson("/api/templates/{$template->id}");

    $response->assertStatus(403);

    expect(BudgetTemplate::find($template->id))->not->toBeNull();
});

test('template name is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/templates', [
            'name' => '',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
