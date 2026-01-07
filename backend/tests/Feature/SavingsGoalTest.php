<?php

use App\Models\Asset;
use App\Models\Role;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalContribution;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    // Create roles if they don't exist
    if (! Role::where('label', 'user')->exists()) {
        Role::create(['label' => 'user']);
    }
    if (! Role::where('label', 'admin')->exists()) {
        Role::create(['label' => 'admin']);
    }
});

test('user can list their savings goals', function () {
    $user = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/savings-goals');

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'targetAmountCents',
                'currentAmountCents',
                'status',
                'progressPercentage',
            ],
        ]);
});

test('user can create a savings goal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/savings-goals', [
            'name' => 'Vacances 2026',
            'description' => 'Voyage en Italie',
            'targetAmountCents' => 200000,
            'startDate' => Carbon::now()->format('Y-m-d'),
            'targetDate' => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'status' => 'active',
            'priority' => 5,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'name' => 'Vacances 2026',
            'targetAmountCents' => 200000,
        ]);

    expect(SavingsGoal::where('user_id', $user->id)->where('name', 'Vacances 2026')->exists())->toBeTrue();
});

test('user can link savings goal to an asset', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/savings-goals', [
            'assetId' => $asset->id,
            'name' => 'Épargne Livret A',
            'targetAmountCents' => 1000000,
            'startDate' => Carbon::now()->format('Y-m-d'),
            'status' => 'active',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'assetId' => $asset->id,
        ]);
});

test('user can update a savings goal', function () {
    $user = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user)->create([
        'name' => 'Ancien Nom',
        'target_amount_cents' => 100000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/savings-goals/{$savingsGoal->id}", [
            'name' => 'Nouveau Nom',
            'targetAmountCents' => 150000,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'name' => 'Nouveau Nom',
            'targetAmountCents' => 150000,
        ]);
});

test('user can change savings goal status', function () {
    $user = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user)->create([
        'status' => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/savings-goals/{$savingsGoal->id}", [
            'status' => 'completed',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'completed',
        ]);
});

test('user can add a contribution to savings goal', function () {
    $user = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user)->create([
        'current_amount_cents' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/savings-goals/{$savingsGoal->id}/contributions", [
            'amountCents' => 5000,
            'contributionDate' => Carbon::now()->format('Y-m-d'),
            'note' => 'Premier versement',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'amountCents',
            'contributionDate',
            'note',
        ]);

    // Vérifier que le montant actuel a été mis à jour
    $savingsGoal->refresh();
    expect($savingsGoal->current_amount_cents)->toBe(5000);
});

test('user can list contributions for a savings goal', function () {
    $user = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user)->create();
    SavingsGoalContribution::factory()
        ->for($savingsGoal)
        ->for($user)
        ->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/savings-goals/{$savingsGoal->id}/contributions");

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['id', 'amountCents', 'contributionDate'],
        ]);
});

test('user can sync savings goal with asset', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->for($user)->create([
        'value_cents' => 75000,
    ]);
    $savingsGoal = SavingsGoal::factory()->for($user)->create([
        'asset_id' => $asset->id,
        'current_amount_cents' => 50000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->patchJson("/api/savings-goals/{$savingsGoal->id}/sync-asset");

    $response->assertStatus(200);

    // Vérifier que le montant a été synchronisé
    $savingsGoal->refresh();
    expect($savingsGoal->current_amount_cents)->toBe(75000);
});

test('user can delete their savings goal', function () {
    $user = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/savings-goals/{$savingsGoal->id}");

    $response->assertStatus(204);

    expect(SavingsGoal::find($savingsGoal->id))->toBeNull();
});

test('user cannot view another users savings goal', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user2)->create();

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/savings-goals/{$savingsGoal->id}");

    $response->assertStatus(403);
});

test('savings goal name is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/savings-goals', [
            'name' => '',
            'targetAmountCents' => 100000,
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('savings goal target amount must be positive', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/savings-goals', [
            'name' => 'Test Goal',
            'targetAmountCents' => -1000,
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['targetAmountCents']);
});

test('contribution amount must be positive', function () {
    $user = User::factory()->create();
    $savingsGoal = SavingsGoal::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/savings-goals/{$savingsGoal->id}/contributions", [
            'amountCents' => -500,
            'contributionDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amountCents']);
});
