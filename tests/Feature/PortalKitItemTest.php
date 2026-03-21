<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitItemFlaggedForInspection;
use Illuminate\Support\Facades\Notification;

function makePortalSetup(string $suffix = ''): array
{
    $client = Client::create([
        'name' => 'Kit Test Client '.$suffix,
        'address' => '1 Kit Street',
        'contact_email' => 'kitclient'.$suffix.'@test.com',
        'phone' => '01234567890',
    ]);

    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    $kitType = KitType::create(['name' => 'Rope '.$suffix, 'interval_months' => 6]);

    return [$client, $user, $kitType];
}

it('allows a client viewer to submit a new kit item with pending review status', function () {
    [$client, $user, $kitType] = makePortalSetup('create');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'kit_type_id' => $kitType->id,
            'asset_tag' => 'CLIENT-001',
            'manufacturer' => 'Petzl',
            'model' => 'Arial',
        ])
        ->assertRedirect(route('portal.kit.index'));

    $item = KitItem::where('asset_tag', 'CLIENT-001')->firstOrFail();

    expect($item->pending_review)->toBeTrue();
    expect($item->client_id)->toBe($client->id);
    expect(AuditLog::where('subject_id', $item->id)->where('action', 'created')->exists())->toBeTrue();
});

it('flags an item for inspection, stores notes, and notifies admins', function () {
    Notification::fake();

    [$client, $user, $kitType] = makePortalSetup('flag');

    $admin = User::factory()->create(['role' => 'admin']);
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'FLAG-001', 'status' => 'in_service']);

    $this->actingAs($user)
        ->patch(route('portal.kit.flag', $item), ['flag_notes' => 'Urgent — job next week'])
        ->assertRedirect(route('portal.kit.show', $item));

    $item->refresh();

    expect($item->flagged_for_inspection)->toBeTrue();
    expect($item->flag_notes)->toBe('Urgent — job next week');
    expect(AuditLog::where('subject_id', $item->id)->where('action', 'updated')->exists())->toBeTrue();

    Notification::assertSentTo($admin, KitItemFlaggedForInspection::class);
});

it('retires an item, clears the flag, and creates an audit log entry', function () {
    [$client, $user, $kitType] = makePortalSetup('retire');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'RETIRE-001',
        'status' => 'in_service',
        'flagged_for_inspection' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('portal.kit.retire', $item))
        ->assertRedirect(route('portal.kit.index'));

    $item->refresh();

    expect($item->status)->toBe('retired');
    expect($item->flagged_for_inspection)->toBeFalse();
    expect(AuditLog::where('subject_id', $item->id)->where('action', 'updated')->exists())->toBeTrue();
});

it('forbids a client viewer from flagging another client\'s item', function () {
    [, $userA] = makePortalSetup('forbid-a');
    [$clientB, , $kitTypeB] = makePortalSetup('forbid-b');

    $otherItem = KitItem::create(['client_id' => $clientB->id, 'kit_type_id' => $kitTypeB->id, 'asset_tag' => 'FORBID-001', 'status' => 'in_service']);

    $this->actingAs($userA)
        ->patch(route('portal.kit.flag', $otherItem))
        ->assertForbidden();
});

it('includes flagged items in the admin dashboard metrics', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    [$client, , $kitType] = makePortalSetup('dash');

    $flagged = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'DASH-FLAG-001',
        'status' => 'in_service',
        'flagged_for_inspection' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('DASH-FLAG-001');
});

it('includes pending review items in the admin dashboard metrics', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    [$client, , $kitType] = makePortalSetup('pending');

    KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'PEND-001',
        'status' => 'in_service',
        'pending_review' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('PEND-001');
});
