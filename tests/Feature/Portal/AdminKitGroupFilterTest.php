<?php

use App\Models\Client;
use App\Models\KitGroup;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

it('shows the Group column on the admin kit list', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::create([
        'name' => 'Admin Group Client',
        'address' => '1 Admin Street',
        'contact_email' => 'admingroup@test.com',
        'phone' => '01234567890',
    ]);

    $kitType = KitType::create(['name' => 'Admin Rope', 'interval_months' => 6]);
    $group = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Personal Set Z']);
    KitItem::create([
        'client_id' => $client->id,
        'kit_group_id' => $group->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'AG-1',
        'status' => 'in_service',
    ]);

    $this->actingAs($admin)
        ->get(route('clients.kit-items.index', $client))
        ->assertOk()
        ->assertSee('Group')
        ->assertSee('Personal Set Z');
});

it('filters admin kit list by group id', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::create([
        'name' => 'Filter Client',
        'address' => '1 Filter Street',
        'contact_email' => 'filter@test.com',
        'phone' => '01234567890',
    ]);

    $kitType = KitType::create(['name' => 'Filter Rope', 'interval_months' => 6]);
    $groupA = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'AAA Group']);
    $groupB = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'BBB Group']);

    $aItem = KitItem::create(['client_id' => $client->id, 'kit_group_id' => $groupA->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'IN-A', 'status' => 'in_service']);
    $bItem = KitItem::create(['client_id' => $client->id, 'kit_group_id' => $groupB->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'IN-B', 'status' => 'in_service']);

    $this->actingAs($admin)
        ->get(route('clients.kit-items.index', ['client' => $client, 'group' => $groupA->id]))
        ->assertOk()
        ->assertSee('IN-A')
        ->assertDontSee('IN-B');
});

it('filters admin kit list by status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::create([
        'name' => 'Status Filter Client',
        'address' => '1 Status Street',
        'contact_email' => 'status-filter@test.com',
        'phone' => '01234567890',
    ]);

    $kitType = KitType::create(['name' => 'Status Rope', 'interval_months' => 6]);

    KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'STATUS-DUE', 'status' => 'inspection_due']);
    KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'STATUS-OK', 'status' => 'in_service']);

    $this->actingAs($admin)
        ->get(route('clients.kit-items.index', ['client' => $client, 'status' => 'inspection_due']))
        ->assertOk()
        ->assertSee('STATUS-DUE')
        ->assertDontSee('STATUS-OK');
});

it('combines admin kit list group and status filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::create([
        'name' => 'Combined Filter Client',
        'address' => '1 Combined Street',
        'contact_email' => 'combined-filter@test.com',
        'phone' => '01234567890',
    ]);

    $kitType = KitType::create(['name' => 'Combined Rope', 'interval_months' => 6]);
    $groupA = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Combined A']);
    $groupB = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Combined B']);

    KitItem::create(['client_id' => $client->id, 'kit_group_id' => $groupA->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'A-DUE', 'status' => 'inspection_due']);
    KitItem::create(['client_id' => $client->id, 'kit_group_id' => $groupA->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'A-OK', 'status' => 'in_service']);
    KitItem::create(['client_id' => $client->id, 'kit_group_id' => $groupB->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'B-DUE', 'status' => 'inspection_due']);

    $this->actingAs($admin)
        ->get(route('clients.kit-items.index', ['client' => $client, 'group' => $groupA->id, 'status' => 'inspection_due']))
        ->assertOk()
        ->assertSee('A-DUE')
        ->assertDontSee('A-OK')
        ->assertDontSee('B-DUE');
});

it('ignores invalid admin kit list status filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::create([
        'name' => 'Invalid Status Filter Client',
        'address' => '1 Invalid Street',
        'contact_email' => 'invalid-status-filter@test.com',
        'phone' => '01234567890',
    ]);

    $kitType = KitType::create(['name' => 'Invalid Status Rope', 'interval_months' => 6]);

    KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'INVALID-DUE', 'status' => 'inspection_due']);
    KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'INVALID-OK', 'status' => 'in_service']);

    $this->actingAs($admin)
        ->get(route('clients.kit-items.index', ['client' => $client, 'status' => 'not-a-status']))
        ->assertOk()
        ->assertSee('INVALID-DUE')
        ->assertSee('INVALID-OK');
});
