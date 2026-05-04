<?php

use App\Models\Client;
use App\Models\Job;
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

it('stores a block of kit items without asset tags', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = makeStoreKitType();

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'quantity' => 5,
            'manufacturer' => 'DMM',
            'model' => 'Phantom',
            'status' => 'in_service',
        ])
        ->assertRedirect(route('clients.kit-items.index', $client));

    expect(KitItem::where('client_id', $client->id)->count())->toBe(5);
    expect(KitItem::where('client_id', $client->id)->whereNull('asset_tag')->count())->toBe(5);
});

it('stores a block of kit items with generated asset tags', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = makeStoreKitType();

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'quantity' => 3,
            'asset_tag_prefix' => 'CAR-',
            'asset_tag_start' => 1,
            'asset_tag_padding' => 3,
            'manufacturer' => 'DMM',
            'status' => 'in_service',
        ])
        ->assertRedirect(route('clients.kit-items.index', $client));

    expect(KitItem::where('client_id', $client->id)->pluck('asset_tag')->sort()->values()->all())
        ->toBe(['CAR-001', 'CAR-002', 'CAR-003']);
});

it('rejects a block when any generated asset tag already exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = makeStoreKitType();

    KitItem::factory()->create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'CAR-002',
    ]);

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'quantity' => 3,
            'asset_tag_prefix' => 'CAR-',
            'asset_tag_start' => 1,
            'asset_tag_padding' => 3,
        ])
        ->assertSessionHasErrors('asset_tag_prefix');

    expect(KitItem::where('client_id', $client->id)->count())->toBe(1);
    expect(KitItem::whereIn('asset_tag', ['CAR-001', 'CAR-003'])->exists())->toBeFalse();
});

it('stores common metadata, due dates and QR codes for a block', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = KitType::create(['name' => 'Block Metadata Type', 'interval_months' => 12]);

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'quantity' => 2,
            'asset_tag_prefix' => 'META-',
            'manufacturer' => 'Petzl',
            'model' => 'OK',
            'first_use_date' => '2026-01-10',
            'swl_kg' => 25,
            'lifting_people' => '1',
            'status' => 'inspection_due',
        ])
        ->assertRedirect();

    $items = KitItem::where('client_id', $client->id)->orderBy('asset_tag')->get();

    expect($items)->toHaveCount(2);
    expect($items->pluck('manufacturer')->unique()->all())->toBe(['Petzl']);
    expect($items->pluck('model')->unique()->all())->toBe(['OK']);
    expect($items->pluck('next_inspection_due')->map->toDateString()->unique()->all())->toBe(['2026-07-10']);
    expect($items->pluck('qr_code')->filter())->toHaveCount(2);
});

it('attaches all block-created kit items to the return_to_job target', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = makeStoreKitType();
    $job = Job::factory()->create(['client_id' => $client->id, 'status' => 'in_progress']);

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', ['client' => $client, 'return_to_job' => $job->id]), [
            'kit_type_id' => $kitType->id,
            'quantity' => 4,
            'asset_tag_prefix' => 'JOB-',
            'status' => 'in_service',
        ])
        ->assertRedirect(route('jobs.show', $job));

    expect($job->kitItems()->count())->toBe(4);
});

it('rejects invalid block quantities', function (int $quantity) {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = makeStoreClient();
    $kitType = makeStoreKitType();

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'quantity' => $quantity,
        ])
        ->assertSessionHasErrors('quantity');
})->with([0, -1, 101]);
