<?php

use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Mail\InvoiceIssued;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

it('emails a client with the PDF attached when an invoice is sent', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'sent_at' => null]);

    $this->actingAs($admin)
        ->post(route('clients.invoices.send', [$client, $invoice]))
        ->assertRedirect();

    Mail::assertQueued(InvoiceIssued::class, function (InvoiceIssued $mail) use ($invoice) {
        return $mail->invoice->is($invoice) && $mail->hasTo('finance@example.com');
    });

    expect($invoice->fresh()->sent_at)->not->toBeNull();
});

it('marks an invoice as paid and fires InvoicePaid', function () {
    Event::fake([InvoicePaid::class]);

    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);

    $this->actingAs($admin)
        ->post(route('clients.invoices.mark-paid', [$client, $invoice]), [
            'payment_method' => 'bank-transfer',
        ])
        ->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Paid);
    expect($invoice->paid_at)->not->toBeNull();
    Event::assertDispatched(InvoicePaid::class);
});

it('refuses to mark a cancelled invoice as paid', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->cancelled()->create(['client_id' => $client->id]);

    $this->actingAs($admin)
        ->post(route('clients.invoices.mark-paid', [$client, $invoice]))
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Cancelled);
});

it('respects the 7-day chase cooldown', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    $invoice = Invoice::factory()->overdue()->create([
        'client_id' => $client->id,
        'last_chase_sent_at' => now()->subDays(2),
    ]);

    $this->actingAs($admin)
        ->post(route('clients.invoices.chase', [$client, $invoice]))
        ->assertRedirect();

    Mail::assertNothingQueued();
});

it('sends a chase reminder for overdue invoices after cooldown', function () {
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create(['contact_email' => 'finance@example.com']);
    $invoice = Invoice::factory()->overdue()->create([
        'client_id' => $client->id,
        'last_chase_sent_at' => now()->subDays(8),
    ]);

    $this->actingAs($admin)
        ->post(route('clients.invoices.chase', [$client, $invoice]))
        ->assertRedirect();

    Mail::assertQueued(InvoiceIssued::class);
    expect($invoice->fresh()->last_chase_sent_at->isToday())->toBeTrue();
});

it('flips eligible sent invoices to overdue via the scheduled command', function () {
    $client = Client::factory()->create();

    $shouldOverdue = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Sent->value,
        'due_date' => now()->subDays(3)->toDateString(),
        'paid_at' => null,
    ]);
    $notDue = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Sent->value,
        'due_date' => now()->addDays(5)->toDateString(),
        'paid_at' => null,
    ]);
    $alreadyPaid = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Sent->value,
        'due_date' => now()->subDays(3)->toDateString(),
        'paid_at' => now(),
    ]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    expect($shouldOverdue->fresh()->status)->toBe(InvoiceStatus::Overdue);
    expect($notDue->fresh()->status)->toBe(InvoiceStatus::Sent);
    expect($alreadyPaid->fresh()->status)->toBe(InvoiceStatus::Sent);
});

it('forbids inspectors from invoice lifecycle actions', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);

    $this->actingAs($inspector)->post(route('clients.invoices.send', [$client, $invoice]))->assertForbidden();
    $this->actingAs($inspector)->post(route('clients.invoices.mark-paid', [$client, $invoice]))->assertForbidden();
    $this->actingAs($inspector)->post(route('clients.invoices.chase', [$client, $invoice]))->assertForbidden();
});
