<?php

use App\Enums\InvoiceStatus;
use App\Models\BankTransaction;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Services\ReconciliationService;

it('suggests invoices within ±£5 and ±14 days for credit transactions', function () {
    $client = Client::factory()->create();
    $bookingDate = '2026-05-01';

    $match = Invoice::factory()->create([
        'client_id' => $client->id,
        'issued_date' => '2026-04-25',
        'total_amount' => 102.00,
        'status' => InvoiceStatus::Sent->value,
    ]);
    $tooFarOff = Invoice::factory()->create([
        'client_id' => $client->id,
        'issued_date' => '2026-04-25',
        'total_amount' => 200.00,
        'status' => InvoiceStatus::Sent->value,
    ]);
    Invoice::factory()->paid()->create([
        'client_id' => $client->id,
        'issued_date' => '2026-04-25',
        'total_amount' => 100.00,
    ]);

    $tx = BankTransaction::factory()->credit(100.00)->create(['booking_date' => $bookingDate]);

    $suggestions = app(ReconciliationService::class)->suggestMatches($tx);

    expect($suggestions->pluck('id')->all())->toContain($match->id);
    expect($suggestions->pluck('id')->all())->not->toContain($tooFarOff->id);
});

it('suggests expenses for debit transactions', function () {
    $expense = Expense::factory()->create([
        'expense_date' => '2026-05-02',
        'amount' => 30.00,
    ]);
    Expense::factory()->reconciled()->create([
        'expense_date' => '2026-05-02',
        'amount' => 30.00,
    ]);

    $tx = BankTransaction::factory()->debit(30.00)->create(['booking_date' => '2026-05-01']);

    $suggestions = app(ReconciliationService::class)->suggestMatches($tx);

    expect($suggestions->pluck('id')->all())->toContain($expense->id);
    expect($suggestions)->toHaveCount(1);
});
