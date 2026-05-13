<?php

use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Models\BankTransaction;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ReconciliationService;
use Illuminate\Support\Facades\Event;

it('matches a full-amount invoice and flips status to Paid', function () {
    Event::fake([InvoicePaid::class]);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $invoice = Invoice::factory()->create([
        'total_amount' => 100.00,
        'status' => InvoiceStatus::Sent->value,
    ]);
    $tx = BankTransaction::factory()->credit(100.00)->create();

    app(ReconciliationService::class)->match($tx, $invoice, 100.00);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    expect($invoice->fresh()->paid_at)->not->toBeNull();
    expect($tx->fresh()->reconciled_at)->not->toBeNull();
    Event::assertDispatched(InvoicePaid::class);
});

it('leaves invoice at Sent on a partial match', function () {
    Event::fake([InvoicePaid::class]);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $invoice = Invoice::factory()->create([
        'total_amount' => 100.00,
        'status' => InvoiceStatus::Sent->value,
    ]);
    $tx = BankTransaction::factory()->credit(50.00)->create();

    app(ReconciliationService::class)->match($tx, $invoice, 50.00);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
    expect($invoice->isPartiallyPaid())->toBeTrue();
    expect($invoice->outstandingAmount())->toBe(50.0);
    Event::assertNotDispatched(InvoicePaid::class);
});

it('flips to Paid once two partial matches add up to the total', function () {
    Event::fake([InvoicePaid::class]);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $invoice = Invoice::factory()->create(['total_amount' => 100.00, 'status' => InvoiceStatus::Sent->value]);
    $tx1 = BankTransaction::factory()->credit(60.00)->create();
    $tx2 = BankTransaction::factory()->credit(40.00)->create();

    app(ReconciliationService::class)->match($tx1, $invoice, 60.00);
    app(ReconciliationService::class)->match($tx2, $invoice, 40.00);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    Event::assertDispatched(InvoicePaid::class);
});

it('reverses paid status on unmatch when no longer fully covered', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $invoice = Invoice::factory()->create(['total_amount' => 100.00, 'status' => InvoiceStatus::Sent->value]);
    $tx = BankTransaction::factory()->credit(100.00)->create();

    $recon = app(ReconciliationService::class)->match($tx, $invoice, 100.00);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    app(ReconciliationService::class)->unmatch($recon->fresh());

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
    expect($invoice->paid_at)->toBeNull();
    expect($tx->fresh()->reconciled_at)->toBeNull();
});

it('matches an expense and stamps reconciled_at', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $expense = Expense::factory()->create(['amount' => 45.50]);
    $tx = BankTransaction::factory()->debit(45.50)->create();

    app(ReconciliationService::class)->match($tx, $expense, 45.50);

    expect($expense->fresh()->reconciled_at)->not->toBeNull();
    expect($tx->fresh()->reconciled_at)->not->toBeNull();
});

it('forbids inspectors from matching', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'total_amount' => 100]);
    $tx = BankTransaction::factory()->credit(100)->create();

    $this->actingAs($inspector)
        ->post(route('accounting.reconciliation.match'), [
            'bank_transaction_id' => $tx->id,
            'matchable_type' => 'invoice',
            'matchable_id' => $invoice->id,
        ])
        ->assertForbidden();
});

it('shows the reconciliation page to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)
        ->get(route('accounting.reconciliation.index'))
        ->assertOk()
        ->assertSee('Unreconciled Bank Transactions');
});
