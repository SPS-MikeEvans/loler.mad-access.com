<?php

use App\Enums\InvoiceStatus;
use App\Events\InvoiceChased;
use App\Mail\InvoiceChaseReminder;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('chases an invoice that is 7+ days overdue and never chased', function () {
    Event::fake([InvoiceChased::class]);

    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Overdue->value,
        'due_date' => now()->subDays(8)->toDateString(),
        'paid_at' => null,
        'last_chase_sent_at' => null,
    ]);

    $this->artisan('invoices:send-chases')->assertSuccessful();

    Mail::assertQueued(InvoiceChaseReminder::class);
    Event::assertDispatched(InvoiceChased::class);
    expect($invoice->fresh()->last_chase_sent_at)->not->toBeNull();
});

it('skips invoices that are less than 7 days overdue', function () {
    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Overdue->value,
        'due_date' => now()->subDays(3)->toDateString(),
        'paid_at' => null,
        'last_chase_sent_at' => null,
    ]);

    $this->artisan('invoices:send-chases')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('respects the 7-day cooldown', function () {
    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Overdue->value,
        'due_date' => now()->subDays(30)->toDateString(),
        'paid_at' => null,
        'last_chase_sent_at' => now()->subDays(2),
    ]);

    $this->artisan('invoices:send-chases')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('chases again after the cooldown', function () {
    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Overdue->value,
        'due_date' => now()->subDays(20)->toDateString(),
        'paid_at' => null,
        'last_chase_sent_at' => now()->subDays(8),
    ]);

    $this->artisan('invoices:send-chases')->assertSuccessful();

    Mail::assertQueued(InvoiceChaseReminder::class);
});

it('does not chase paid or cancelled invoices', function () {
    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    Invoice::factory()->paid()->create([
        'client_id' => $client->id,
        'due_date' => now()->subDays(30)->toDateString(),
    ]);
    Invoice::factory()->cancelled()->create([
        'client_id' => $client->id,
        'due_date' => now()->subDays(30)->toDateString(),
    ]);

    $this->artisan('invoices:send-chases')->assertSuccessful();

    Mail::assertNothingQueued();
});
