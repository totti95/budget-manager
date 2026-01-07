<?php

use App\Models\BudgetTemplate;
use App\Models\RecurringExpense;
use App\Models\Role;
use App\Models\TemplateCategory;
use App\Models\TemplateSubcategory;
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

test('user can list their recurring expenses', function () {
    $user = User::factory()->create();
    $recurringExpense = RecurringExpense::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/recurring-expenses');

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['id', 'label', 'amountCents', 'frequency', 'isActive'],
        ]);
});

test('user can create a monthly recurring expense', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/recurring-expenses', [
            'label' => 'Loyer',
            'amountCents' => 85000,
            'frequency' => 'monthly',
            'dayOfMonth' => 1,
            'autoCreate' => true,
            'isActive' => true,
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id', 'label', 'amountCents', 'frequency', 'dayOfMonth',
        ])
        ->assertJson([
            'label' => 'Loyer',
            'amountCents' => 85000,
            'frequency' => 'monthly',
            'dayOfMonth' => 1,
        ]);

    expect(RecurringExpense::where('user_id', $user->id)->where('label', 'Loyer')->exists())->toBeTrue();
});

test('user can create a weekly recurring expense', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/recurring-expenses', [
            'label' => 'Marché',
            'amountCents' => 5000,
            'frequency' => 'weekly',
            'dayOfWeek' => 'saturday',
            'autoCreate' => true,
            'isActive' => true,
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'label' => 'Marché',
            'frequency' => 'weekly',
            'dayOfWeek' => 'saturday',
        ]);
});

test('user can create a yearly recurring expense', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/recurring-expenses', [
            'label' => 'Assurance Habitation',
            'amountCents' => 25000,
            'frequency' => 'yearly',
            'dayOfMonth' => 15,
            'monthOfYear' => 9,
            'autoCreate' => true,
            'isActive' => true,
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'label' => 'Assurance Habitation',
            'frequency' => 'yearly',
            'monthOfYear' => 9,
        ]);
});

test('user can link recurring expense to template subcategory', function () {
    $user = User::factory()->create();
    $template = BudgetTemplate::factory()->for($user)->create();
    $category = TemplateCategory::factory()->for($template, 'budgetTemplate')->create();
    $subcategory = TemplateSubcategory::factory()->for($category, 'templateCategory')->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/recurring-expenses', [
            'templateSubcategoryId' => $subcategory->id,
            'label' => 'Netflix',
            'amountCents' => 1499,
            'frequency' => 'monthly',
            'dayOfMonth' => 5,
            'autoCreate' => true,
            'isActive' => true,
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'templateSubcategoryId' => $subcategory->id,
        ]);
});

test('user can update a recurring expense', function () {
    $user = User::factory()->create();
    $recurringExpense = RecurringExpense::factory()->for($user)->create([
        'label' => 'Ancien Label',
        'amount_cents' => 1000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/recurring-expenses/{$recurringExpense->id}", [
            'label' => 'Nouveau Label',
            'amountCents' => 2000,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'label' => 'Nouveau Label',
            'amountCents' => 2000,
        ]);
});

test('user can toggle recurring expense active status', function () {
    $user = User::factory()->create();
    $recurringExpense = RecurringExpense::factory()->for($user)->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->patchJson("/api/recurring-expenses/{$recurringExpense->id}/toggle-active");

    $response->assertStatus(200)
        ->assertJson([
            'isActive' => false,
        ]);

    $recurringExpense->refresh();
    expect($recurringExpense->is_active)->toBeFalse();
});

test('user can delete their recurring expense', function () {
    $user = User::factory()->create();
    $recurringExpense = RecurringExpense::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/recurring-expenses/{$recurringExpense->id}");

    $response->assertStatus(204);

    expect(RecurringExpense::find($recurringExpense->id))->toBeNull();
});

test('user cannot view another users recurring expense', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $recurringExpense = RecurringExpense::factory()->for($user2)->create();

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/recurring-expenses/{$recurringExpense->id}");

    $response->assertStatus(403);
});

test('recurring expense label is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/recurring-expenses', [
            'label' => '',
            'amountCents' => 1000,
            'frequency' => 'monthly',
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['label']);
});

test('recurring expense frequency must be valid', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/recurring-expenses', [
            'label' => 'Test',
            'amountCents' => 1000,
            'frequency' => 'invalid_frequency',
            'startDate' => Carbon::now()->format('Y-m-d'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['frequency']);
});
