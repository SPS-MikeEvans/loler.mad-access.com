<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('shows the expense index to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('accounting.expenses.index'))
        ->assertOk()
        ->assertSee('All Expenses');
});

it('forbids inspectors from viewing expenses', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)
        ->get(route('accounting.expenses.index'))
        ->assertForbidden();
});

it('stores an expense without a receipt', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cat = ExpenseCategory::factory()->create();

    $this->actingAs($admin)
        ->post(route('accounting.expenses.store'), [
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $cat->id,
            'supplier' => 'Screwfix',
            'amount' => '42.50',
            'notes' => 'Replacement bits',
        ])
        ->assertRedirect();

    $expense = Expense::first();
    expect($expense)->not->toBeNull();
    expect((float) $expense->amount)->toBe(42.50);
    expect($expense->supplier)->toBe('Screwfix');
    expect($expense->receipt_path)->toBeNull();
});

it('stores an expense with a private receipt file', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $cat = ExpenseCategory::factory()->create();

    $file = UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('accounting.expenses.store'), [
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $cat->id,
            'supplier' => 'Screwfix',
            'amount' => '42.50',
            'receipt' => $file,
        ])
        ->assertRedirect();

    $expense = Expense::first();
    expect($expense->receipt_path)->not->toBeNull();
    Storage::disk('local')->assertExists($expense->receipt_path);
});

it('downloads a receipt as admin', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $expense = Expense::factory()->create();

    $path = "expenses/{$expense->id}/test.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 fake');
    $expense->update(['receipt_path' => $path]);

    $this->actingAs($admin)
        ->get(route('accounting.expenses.receipt', $expense))
        ->assertOk();
});

it('rejects receipts over 5MB', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $cat = ExpenseCategory::factory()->create();

    $big = UploadedFile::fake()->create('big.pdf', 6 * 1024, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('accounting.expenses.store'), [
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $cat->id,
            'supplier' => 'Vendor',
            'amount' => '10',
            'receipt' => $big,
        ])
        ->assertSessionHasErrors('receipt');
});

it('updates an expense and replaces the receipt', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $cat = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create();

    $oldPath = "expenses/{$expense->id}/old.pdf";
    Storage::disk('local')->put($oldPath, 'old');
    $expense->update(['receipt_path' => $oldPath]);

    $newFile = UploadedFile::fake()->create('new.pdf', 50, 'application/pdf');

    $this->actingAs($admin)
        ->put(route('accounting.expenses.update', $expense), [
            'expense_date' => $expense->expense_date->toDateString(),
            'expense_category_id' => $cat->id,
            'supplier' => 'Updated',
            'amount' => '99.99',
            'receipt' => $newFile,
        ])
        ->assertRedirect();

    $expense->refresh();
    expect($expense->supplier)->toBe('Updated');
    expect($expense->receipt_path)->not->toBe($oldPath);
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($expense->receipt_path);
});

it('lists expenses filtered by category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $catA = ExpenseCategory::factory()->create();
    $catB = ExpenseCategory::factory()->create();

    $a = Expense::factory()->create(['expense_category_id' => $catA->id, 'supplier' => 'A-vendor']);
    Expense::factory()->create(['expense_category_id' => $catB->id, 'supplier' => 'B-vendor']);

    $this->actingAs($admin)
        ->get(route('accounting.expenses.index', ['category' => $catA->id]))
        ->assertOk()
        ->assertSee('A-vendor')
        ->assertDontSee('B-vendor');
});
