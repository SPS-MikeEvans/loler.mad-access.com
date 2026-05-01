<?php

use App\Models\Client;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeStoreClient(): Client
{
    return Client::create([
        'name' => 'Store Test Client',
        'address' => '1 Store Street',
        'contact_email' => 'store@example.test',
        'phone' => '01234567890',
    ]);
}

function makeStoreKitType(): KitType
{
    return KitType::create([
        'name' => 'Store Test Type',
        'interval_months' => 6,
    ]);
}

it('stores a kit item without an asset tag', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = makeStoreKitType();

    $response = $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'manufacturer' => 'Petzl',
            'model' => 'Bod Fast',
            'serial_no' => 'SN-12345',
            'purchase_date' => '2022-04-01',
            'first_use_date' => '2022-04-08',
            'swl_kg' => 140,
            'lifting_people' => '1',
            'status' => 'in_service',
        ]);

    $response->assertRedirect(route('clients.kit-items.index', $client));

    expect(KitItem::query()->where('client_id', $client->id)->count())->toBe(1);
    expect(KitItem::query()->where('client_id', $client->id)->first()->asset_tag)->toBeNull();
});

it('allows multiple kit items with no asset tag', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = makeStoreKitType();

    KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'manufacturer' => 'Petzl',
            'serial_no' => 'SN-22222',
            'status' => 'in_service',
        ])
        ->assertRedirect();

    expect(KitItem::query()->whereNull('asset_tag')->count())->toBe(2);
});
