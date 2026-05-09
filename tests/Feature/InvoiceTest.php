<?php

use App\Models\Client;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeInvoiceClient(string $suffix = ''): Client
{
    return Client::create([
        'name' => 'Invoice Client '.$suffix,
        'address' => '1 Invoice Street',
        'contact_email' => "invoice-{$suffix}@example.test",
        'phone' => '01234567890',
    ]);
}

function makeInvoiceKitType(): KitType
{
    return KitType::create([
        'name' => 'Invoice Type '.uniqid(),
        'interval_months' => 6,
    ]);
}

function makeInvoiceableInspection(Client $client, KitType $kitType, User $inspector, float $cost, string $date): Inspection
{
    $kitItem = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'KIT-'.uniqid(),
        'status' => 'in_service',
    ]);

    return Inspection::create([
        'kit_item_id' => $kitItem->id,
        'inspector_user_id' => $inspector->id,
        'status' => 'complete',
        'inspection_date' => $date,
        'next_due_date' => '2027-01-01',
        'overall_status' => 'pass',
        'cost' => $cost,
    ]);
}

it('creates an invoice with no waivers and no discount', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('plain');
    $kitType = makeInvoiceKitType();

    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');
    makeInvoiceableInspection($client, $kitType, $admin, 50, '2026-04-15');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
        ])
        ->assertRedirect();

    $invoice = Invoice::where('client_id', $client->id)->first();

    expect((float) $invoice->subtotal)->toBe(150.00);
    expect((float) $invoice->total_amount)->toBe(150.00);
    expect($invoice->discount_percent)->toBeNull();
    expect($invoice->inspections()->where('invoice_waived', true)->count())->toBe(0);
});

it('waives selected inspections and excludes them from the subtotal', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('waive');
    $kitType = makeInvoiceKitType();

    $keep = makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');
    $waive = makeInvoiceableInspection($client, $kitType, $admin, 75, '2026-04-10');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'waived_inspection_ids' => [$waive->id],
        ])
        ->assertRedirect();

    $invoice = Invoice::where('client_id', $client->id)->first();

    expect((float) $invoice->subtotal)->toBe(100.00);
    expect((float) $invoice->total_amount)->toBe(100.00);
    expect($waive->refresh()->invoice_waived)->toBeTrue();
    expect($keep->refresh()->invoice_waived)->toBeFalse();
});

it('applies a percentage discount to the total', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('pct');
    $kitType = makeInvoiceKitType();

    makeInvoiceableInspection($client, $kitType, $admin, 200, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'discount_percent' => 10,
        ])
        ->assertRedirect();

    $invoice = Invoice::where('client_id', $client->id)->first();

    expect((float) $invoice->subtotal)->toBe(200.00);
    expect((float) $invoice->discount_percent)->toBe(10.00);
    expect((float) $invoice->total_amount)->toBe(180.00);
});

it('back-calculates a discount percentage from a fixed total', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('fixed');
    $kitType = makeInvoiceKitType();

    makeInvoiceableInspection($client, $kitType, $admin, 237, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'fixed_total' => 200,
        ])
        ->assertRedirect();

    $invoice = Invoice::where('client_id', $client->id)->first();

    expect((float) $invoice->subtotal)->toBe(237.00);
    expect((float) $invoice->total_amount)->toBe(200.00);
    expect((float) $invoice->discount_percent)->toBe(15.61);
});

it('rejects a submission that supplies both discount percent and fixed total', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('both');
    $kitType = makeInvoiceKitType();

    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'discount_percent' => 10,
            'fixed_total' => 80,
        ])
        ->assertSessionHasErrors('discount_percent');

    expect(Invoice::query()->where('client_id', $client->id)->count())->toBe(0);
});

it('rejects a fixed total that is greater than or equal to the subtotal', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('toobig');
    $kitType = makeInvoiceKitType();

    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'fixed_total' => 150,
        ])
        ->assertSessionHasErrors('fixed_total');

    expect(Invoice::query()->where('client_id', $client->id)->count())->toBe(0);
});

it('ignores waived inspection ids that do not belong to the eligible set', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $clientA = makeInvoiceClient('a');
    $clientB = makeInvoiceClient('b');
    $kitType = makeInvoiceKitType();

    $eligible = makeInvoiceableInspection($clientA, $kitType, $admin, 100, '2026-04-01');
    $foreign = makeInvoiceableInspection($clientB, $kitType, $admin, 50, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $clientA), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'waived_inspection_ids' => [$foreign->id, $eligible->id],
        ])
        ->assertRedirect();

    $invoice = Invoice::where('client_id', $clientA->id)->first();

    expect((float) $invoice->subtotal)->toBe(0.00);
    expect((float) $invoice->total_amount)->toBe(0.00);
    expect($eligible->refresh()->invoice_waived)->toBeTrue();
    expect($foreign->refresh()->invoice_waived)->toBeFalse();
    expect($foreign->refresh()->invoice_id)->toBeNull();
});

it('renders the show view with subtotal, discount and waived line styling', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('show');
    $kitType = makeInvoiceKitType();
    $waive = makeInvoiceableInspection($client, $kitType, $admin, 75, '2026-04-01');
    makeInvoiceableInspection($client, $kitType, $admin, 200, '2026-04-15');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'waived_inspection_ids' => [$waive->id],
            'discount_percent' => 10,
        ]);

    $invoice = Invoice::where('client_id', $client->id)->first();

    $response = $this->actingAs($admin)
        ->get(route('clients.invoices.show', [$client, $invoice]));

    $response->assertOk();
    $response->assertSee('Subtotal');
    $response->assertSee('Discount (10.00%)');
    $response->assertSee('Waived');
});

it('renders the discount and waived markers in the pdf view', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('pdf');
    $kitType = makeInvoiceKitType();
    $waive = makeInvoiceableInspection($client, $kitType, $admin, 50, '2026-04-01');
    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-15');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'waived_inspection_ids' => [$waive->id],
            'fixed_total' => 80,
        ]);

    $invoice = Invoice::where('client_id', $client->id)->first()
        ->load(['client', 'inspections.kitItem.kitType', 'inspections.inspector']);

    $html = view('pdf.invoice', [
        'invoice' => $invoice,
        'company_name' => config('company.name'),
        'company' => config('company'),
    ])->render();

    expect($html)->toContain('Subtotal');
    expect($html)->toContain('Discount (');
    expect($html)->toContain('(Waived)');
});

it('resets invoice_waived when the invoice is destroyed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('destroy');
    $kitType = makeInvoiceKitType();
    $waive = makeInvoiceableInspection($client, $kitType, $admin, 50, '2026-04-01');
    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-15');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
            'waived_inspection_ids' => [$waive->id],
        ]);

    $invoice = Invoice::where('client_id', $client->id)->first();

    expect($waive->refresh()->invoice_waived)->toBeTrue();

    $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $invoice]));

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.invoices.destroy', [$client, $invoice]), [
            'confirmation_phrase' => "DELETE-INVOICE-{$invoice->id}",
        ])
        ->assertRedirect(route('clients.show', $client));

    expect($waive->refresh()->invoice_waived)->toBeFalse();
    expect($waive->refresh()->invoice_id)->toBeNull();
});

it('soft-deletes the invoice row instead of hard-deleting it', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('softdel');
    $kitType = makeInvoiceKitType();
    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
        ]);

    $invoice = Invoice::where('client_id', $client->id)->first();

    $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $invoice]));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.invoices.destroy', [$client, $invoice]), [
            'confirmation_phrase' => "DELETE-INVOICE-{$invoice->id}",
        ]);

    expect(Invoice::find($invoice->id))->toBeNull();
    expect(Invoice::withTrashed()->find($invoice->id)?->deleted_at)->not->toBeNull();
});

it('does not reuse invoice numbers after a soft-delete', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('numbering');
    $kitType = makeInvoiceKitType();
    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
        ]);
    $first = Invoice::where('client_id', $client->id)->first();
    expect($first)->not->toBeNull();

    $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $first]));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.invoices.destroy', [$client, $first]), [
            'confirmation_phrase' => "DELETE-INVOICE-{$first->id}",
        ]);

    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-05-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-05-01',
            'period_to' => '2026-05-31',
        ]);
    $second = Invoice::where('client_id', $client->id)->first();

    expect($second)->not->toBeNull();
    expect($second->invoice_number)->not->toBe($first->invoice_number);
    expect((int) substr($second->invoice_number, -3))->toBeGreaterThan((int) substr($first->invoice_number, -3));
});

it('returns 404 when viewing a soft-deleted invoice', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('show404');
    $kitType = makeInvoiceKitType();
    makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
        ]);
    $invoice = Invoice::where('client_id', $client->id)->first();

    $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $invoice]));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.invoices.destroy', [$client, $invoice]), [
            'confirmation_phrase' => "DELETE-INVOICE-{$invoice->id}",
        ]);

    $this->actingAs($admin)
        ->get(route('clients.invoices.show', [$client, $invoice->id]))
        ->assertNotFound();
});

it('does not re-link inspections when a soft-deleted invoice is restored', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeInvoiceClient('restore');
    $kitType = makeInvoiceKitType();
    $inspection = makeInvoiceableInspection($client, $kitType, $admin, 100, '2026-04-01');

    $this->actingAs($admin)
        ->post(route('clients.invoices.store', $client), [
            'period_from' => '2026-04-01',
            'period_to' => '2026-04-30',
        ]);
    $invoice = Invoice::where('client_id', $client->id)->first();

    $this->actingAs($admin)->get(route('clients.invoices.show', [$client, $invoice]));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.invoices.destroy', [$client, $invoice]), [
            'confirmation_phrase' => "DELETE-INVOICE-{$invoice->id}",
        ]);

    Invoice::withTrashed()->find($invoice->id)->restore();

    expect($inspection->refresh()->invoice_id)->toBeNull();
    expect($inspection->refresh()->invoice_waived)->toBeFalse();
});
