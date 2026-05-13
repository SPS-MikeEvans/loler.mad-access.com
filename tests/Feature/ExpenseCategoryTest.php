<?php

use App\Models\ExpenseCategory;
use App\Models\User;

it('shows the categories index to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('accounting.expense-categories.index'))
        ->assertOk()
        ->assertSee('Manage Categories');
});

it('forbids inspectors from viewing categories', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)
        ->get(route('accounting.expense-categories.index'))
        ->assertForbidden();
});

it('lets an admin add a category', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('accounting.expense-categories.store'), ['name' => 'Travel'])
        ->assertRedirect(route('accounting.expense-categories.index'));

    expect(ExpenseCategory::where('name', 'Travel')->where('is_active', true)->exists())->toBeTrue();
});

it('rejects duplicate category names', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    ExpenseCategory::create(['name' => 'Travel', 'is_active' => true]);

    $this->actingAs($admin)
        ->post(route('accounting.expense-categories.store'), ['name' => 'Travel'])
        ->assertSessionHasErrors('name');
});

it('toggles is_active on update', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cat = ExpenseCategory::create(['name' => 'Fuel', 'is_active' => true]);

    $this->actingAs($admin)
        ->patch(route('accounting.expense-categories.update', $cat), ['is_active' => '0'])
        ->assertRedirect();

    expect($cat->refresh()->is_active)->toBeFalse();
});
