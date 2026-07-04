<?php

use App\Models\Client;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeUpdateClient(): Client
{
    return Client::create([
        'name' => 'Update Test Client',
        'address' => '1 Update Street',
        'contact_email' => 'update@example.test',
        'phone' => '01234567890',
    ]);
}

function makeUpdateKitType(int $intervalMonths = 6): KitType
{
    return KitType::create([
        'name' => 'Update Test Type',
        'interval_months' => $intervalMonths,
    ]);
}

it('recalculates the due date from the kit type when left blank', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeUpdateClient();
    $kitType = makeUpdateKitType(12);
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
    ]);

    $this->actingAs($admin)
        ->put(route('clients.kit-items.update', [$client, $item]), [
            'kit_type_id' => $kitType->id,
            'first_use_date' => '2026-01-10',
            'status' => 'in_service',
        ])
        ->assertRedirect(route('clients.kit-items.show', [$client, $item]));

    expect($item->refresh()->next_inspection_due->toDateString())->toBe('2027-01-10');
});

it('updates a typed kit item to a custom type name', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeUpdateClient();
    $kitType = makeUpdateKitType();
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'first_use_date' => '2026-01-10',
    ]);

    $this->actingAs($admin)
        ->put(route('clients.kit-items.update', [$client, $item]), [
            'custom_type_name' => 'Off-list Winch',
            'status' => 'in_service',
        ])
        ->assertRedirect(route('clients.kit-items.show', [$client, $item]));

    $item->refresh();

    expect($item->kit_type_id)->toBeNull();
    expect($item->custom_type_name)->toBe('Off-list Winch');
    expect($item->next_inspection_due)->toBeNull();
});

it('assigns a catalog type to a custom item and clears the custom name', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeUpdateClient();
    $kitType = makeUpdateKitType(6);
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => null,
        'custom_type_name' => 'Pending Custom Item',
    ]);

    $this->actingAs($admin)
        ->put(route('clients.kit-items.update', [$client, $item]), [
            'kit_type_id' => $kitType->id,
            'custom_type_name' => '',
            'first_use_date' => '2026-02-01',
            'status' => 'in_service',
        ])
        ->assertRedirect(route('clients.kit-items.show', [$client, $item]));

    $item->refresh();

    expect($item->kit_type_id)->toBe($kitType->id);
    expect($item->custom_type_name)->toBeNull();
    expect($item->next_inspection_due->toDateString())->toBe('2026-08-01');
});

it('rejects an update with neither a kit type nor a custom name', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeUpdateClient();
    $kitType = makeUpdateKitType();
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
    ]);

    $this->actingAs($admin)
        ->put(route('clients.kit-items.update', [$client, $item]), [
            'status' => 'in_service',
        ])
        ->assertSessionHasErrors(['kit_type_id', 'custom_type_name']);

    expect($item->refresh()->kit_type_id)->toBe($kitType->id);
});

it('stores an explicit due date on a custom item', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeUpdateClient();
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => null,
        'custom_type_name' => 'Custom Hoist',
    ]);

    $this->actingAs($admin)
        ->put(route('clients.kit-items.update', [$client, $item]), [
            'custom_type_name' => 'Custom Hoist',
            'next_inspection_due' => '2026-12-01',
            'status' => 'in_service',
        ])
        ->assertRedirect(route('clients.kit-items.show', [$client, $item]));

    expect($item->refresh()->next_inspection_due->toDateString())->toBe('2026-12-01');
});

it('shows and edits a custom kit item without a server error', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeUpdateClient();
    makeUpdateKitType();
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => null,
        'custom_type_name' => 'Portal Submitted Item',
    ]);

    $this->actingAs($admin)
        ->get(route('clients.kit-items.show', [$client, $item]))
        ->assertSuccessful()
        ->assertSee('Portal Submitted Item');

    $this->actingAs($admin)
        ->get(route('clients.kit-items.edit', [$client, $item]))
        ->assertSuccessful()
        ->assertSee('Portal Submitted Item');
});

it('blocks recording an inspection for a custom kit item', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeUpdateClient();
    $item = KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => null,
        'custom_type_name' => 'Custom Item Awaiting Type',
    ]);

    $this->actingAs($admin)
        ->get(route('clients.kit-items.inspections.create', [$client, $item]))
        ->assertRedirect(route('clients.kit-items.show', [$client, $item]))
        ->assertSessionHas('error');
});
