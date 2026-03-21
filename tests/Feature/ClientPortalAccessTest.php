<?php

use App\Models\Client;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makePortalClient(string $emailSuffix = ''): array
{
    $client = Client::create([
        'name' => 'Portal Client '.$emailSuffix,
        'address' => '1 Portal Street',
        'contact_email' => 'client'.$emailSuffix.'@portal.test',
        'phone' => '01234567890',
    ]);

    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    return [$client, $user];
}

it('allows a client viewer to access the portal dashboard', function () {
    [, $user] = makePortalClient('a');

    $this->actingAs($user)
        ->get(route('portal.dashboard'))
        ->assertOk();
});

it('forbids a client viewer from accessing the admin clients index', function () {
    [, $user] = makePortalClient('b');

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertForbidden();
});

it('redirects a client viewer from the admin dashboard to the portal dashboard', function () {
    [, $user] = makePortalClient('c');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('portal.dashboard'));
});

it('shows only the client viewer\'s own kit items on the portal kit index', function () {
    [$clientA, $userA] = makePortalClient('d');
    [$clientB] = makePortalClient('e');

    $kitType = KitType::create(['name' => 'Harness', 'interval_months' => 6]);

    $ownItem = KitItem::create(['client_id' => $clientA->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'OWN-001', 'status' => 'in_service']);
    $otherItem = KitItem::create(['client_id' => $clientB->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'OTHER-001', 'status' => 'in_service']);

    $response = $this->actingAs($userA)->get(route('portal.kit.index'));

    $response->assertOk();
    $response->assertSee('OWN-001');
    $response->assertDontSee('OTHER-001');
});

it('forbids a client viewer from viewing another client\'s kit item', function () {
    [, $userA] = makePortalClient('f');
    [$clientB] = makePortalClient('g');

    $kitType = KitType::create(['name' => 'Connector', 'interval_months' => 6]);
    $otherItem = KitItem::create(['client_id' => $clientB->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'FORBIDDEN-001', 'status' => 'in_service']);

    $this->actingAs($userA)
        ->get(route('portal.kit.show', $otherItem))
        ->assertForbidden();
});
