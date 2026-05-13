<?php

use App\Enums\InvoiceStatus;
use App\Exceptions\InvalidInvoiceTransition;
use App\Models\Invoice;

it('enumerates allowed transitions correctly', function () {
    expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Sent))->toBeTrue();
    expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Paid))->toBeFalse();

    expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::Paid))->toBeTrue();
    expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::Overdue))->toBeTrue();
    expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::Cancelled))->toBeTrue();
    expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::Draft))->toBeFalse();

    expect(InvoiceStatus::Overdue->canTransitionTo(InvoiceStatus::Paid))->toBeTrue();
    expect(InvoiceStatus::Overdue->canTransitionTo(InvoiceStatus::Draft))->toBeFalse();

    expect(InvoiceStatus::Paid->isTerminal())->toBeTrue();
    expect(InvoiceStatus::Cancelled->isTerminal())->toBeTrue();

    expect(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Sent))->toBeFalse();
    expect(InvoiceStatus::Cancelled->canTransitionTo(InvoiceStatus::Paid))->toBeFalse();
});

it('allows transitionTo for a valid path on the model', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent->value]);

    $invoice->transitionTo(InvoiceStatus::Paid);

    expect($invoice->status)->toBe(InvoiceStatus::Paid);
});

it('throws InvalidInvoiceTransition when moving from a terminal state', function () {
    $invoice = Invoice::factory()->cancelled()->create();

    expect(fn () => $invoice->transitionTo(InvoiceStatus::Paid))
        ->toThrow(InvalidInvoiceTransition::class);
});

it('blocks the Paid->Sent backslide', function () {
    $invoice = Invoice::factory()->paid()->create();

    expect(fn () => $invoice->transitionTo(InvoiceStatus::Sent))
        ->toThrow(InvalidInvoiceTransition::class);
});
