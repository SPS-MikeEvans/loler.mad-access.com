<?php

use App\Models\Client;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\KitGroup;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeSoftDelClient(string $suffix = ''): Client
{
    return Client::create([
        'name' => 'Soft Client '.$suffix,
        'address' => '1 Soft Street',
        'contact_email' => "soft-{$suffix}@example.test",
        'phone' => '01234567890',
    ]);
}

function makeSoftDelKitType(string $suffix = ''): KitType
{
    return KitType::create([
        'name' => 'Soft Type '.$suffix,
        'interval_months' => 6,
    ]);
}

it('soft-deletes a kit type via the controller path', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $kitType = makeSoftDelKitType('controller');

    $this->actingAs($admin)->get(route('kit-types.index'));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('kit-types.destroy', $kitType), [
            'confirmation_phrase' => "DELETE-TYPE-{$kitType->id}",
        ])
        ->assertRedirect();

    expect(KitType::find($kitType->id))->toBeNull();
    expect(KitType::withTrashed()->find($kitType->id)?->deleted_at)->not->toBeNull();
});

it('cascade soft-deletes a client and all of its data', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeSoftDelClient('cascade');
    $kitType = makeSoftDelKitType('cascade');

    $kitItem = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'CASCADE-1',
        'status' => 'in_service',
    ]);
    $kitGroup = KitGroup::create([
        'client_id' => $client->id,
        'name' => 'Group A',
    ]);
    $inspection = Inspection::create([
        'kit_item_id' => $kitItem->id,
        'inspector_user_id' => $admin->id,
        'status' => 'complete',
        'inspection_date' => '2026-04-01',
        'next_due_date' => '2027-01-01',
        'overall_status' => 'pass',
        'cost' => 100,
    ]);
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-2026-9001',
        'issued_date' => '2026-04-01',
        'period_from' => '2026-04-01',
        'period_to' => '2026-04-30',
        'subtotal' => 100,
        'total_amount' => 100,
    ]);
    $inspection->update(['invoice_id' => $invoice->id]);

    $portalUser = User::factory()->create([
        'role' => 'client_viewer',
        'client_id' => $client->id,
    ]);

    $this->actingAs($admin)->get(route('clients.index'));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.destroy', $client), [
            'confirmation_phrase' => "DELETE-CLIENT-{$client->id}",
        ])
        ->assertRedirect(route('clients.index'));

    expect(Client::find($client->id))->toBeNull();
    expect(Client::withTrashed()->find($client->id)?->deleted_at)->not->toBeNull();

    expect(KitItem::find($kitItem->id))->toBeNull();
    expect(KitItem::withTrashed()->find($kitItem->id)?->deleted_at)->not->toBeNull();

    expect(KitGroup::find($kitGroup->id))->toBeNull();
    expect(KitGroup::withTrashed()->find($kitGroup->id)?->deleted_at)->not->toBeNull();

    expect(Invoice::find($invoice->id))->toBeNull();
    expect(Invoice::withTrashed()->find($invoice->id)?->deleted_at)->not->toBeNull();

    expect($inspection->refresh()->invoice_id)->toBeNull();
    expect($inspection->refresh()->invoice_waived)->toBeFalse();

    expect($portalUser->refresh()->client_id)->toBeNull();
    expect(User::find($portalUser->id))->not->toBeNull();
});

it('runs the cascade when delete is called on the model directly', function () {
    $client = makeSoftDelClient('direct');
    $kitType = makeSoftDelKitType('direct');
    $kitItem = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'DIRECT-1',
        'status' => 'in_service',
    ]);

    $client->delete();

    expect(Client::withTrashed()->find($client->id)?->deleted_at)->not->toBeNull();
    expect(KitItem::withTrashed()->find($kitItem->id)?->deleted_at)->not->toBeNull();
});

it('blocks client deletion when an active job exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeSoftDelClient('blocked');
    Job::factory()->create(['client_id' => $client->id, 'status' => 'in_progress']);

    $this->actingAs($admin)->get(route('clients.index'));
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('clients.destroy', $client), [
            'confirmation_phrase' => "DELETE-CLIENT-{$client->id}",
        ])
        ->assertSessionHasErrors('confirmation_phrase');

    expect(Client::find($client->id))->not->toBeNull();
    expect(Client::withTrashed()->find($client->id)?->deleted_at)->toBeNull();
});

it('does not auto-restore children when a trashed client is restored', function () {
    $client = makeSoftDelClient('restore');
    $kitType = makeSoftDelKitType('restore');
    $kitItem = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'RESTORE-1',
        'status' => 'in_service',
    ]);

    $client->delete();
    Client::withTrashed()->find($client->id)->restore();

    expect(Client::find($client->id))->not->toBeNull();
    expect(KitItem::find($kitItem->id))->toBeNull();
    expect(KitItem::withTrashed()->find($kitItem->id)?->deleted_at)->not->toBeNull();
});
