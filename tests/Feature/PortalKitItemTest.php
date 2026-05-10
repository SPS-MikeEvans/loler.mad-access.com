<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\KitGroup;
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

it('allows a client viewer to submit a block of normal kit items for review', function () {
    [$client, $user, $kitType] = makePortalSetup('block-normal');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'kit_type_id' => $kitType->id,
            'quantity' => 3,
            'asset_tag_prefix' => 'P-CAR-',
            'asset_tag_start' => 7,
            'asset_tag_padding' => 2,
            'manufacturer' => 'DMM',
        ])
        ->assertRedirect(route('portal.kit.index'));

    $items = KitItem::where('client_id', $client->id)->orderBy('asset_tag')->get();

    expect($items)->toHaveCount(3);
    expect($items->pluck('asset_tag')->all())->toBe(['P-CAR-07', 'P-CAR-08', 'P-CAR-09']);
    expect($items->every(fn (KitItem $item) => $item->pending_review))->toBeTrue();
});

it('allows a client viewer to submit a block of custom kit items for review', function () {
    [$client, $user] = makePortalSetup('block-custom');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'custom_type_name' => 'Wire Strop Lot',
            'quantity' => 2,
            'asset_tag_prefix' => 'WS-',
        ])
        ->assertRedirect(route('portal.kit.index'));

    $items = KitItem::where('client_id', $client->id)->orderBy('asset_tag')->get();

    expect($items)->toHaveCount(2);
    expect($items->pluck('custom_type_name')->unique()->all())->toBe(['Wire Strop Lot']);
    expect($items->every(fn (KitItem $item) => $item->pending_review))->toBeTrue();
});

it('flags an item for inspection, stores notes, and notifies admins', function () {
    Notification::fake();

    [$client, $user, $kitType] = makePortalSetup('flag');

    $admin = User::factory()->create(['role' => 'admin']);
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'FLAG-001', 'status' => 'in_service']);

    $this->actingAs($user)
        ->post(route('portal.kit.flag', $item), ['flag_notes' => 'Urgent — job next week'])
        ->assertRedirect(route('portal.kit.show', $item));

    $item->refresh();

    expect($item->flagged_for_inspection)->toBeTrue();
    expect($item->flag_notes)->toBe('Urgent — job next week');
    expect(AuditLog::where('subject_id', $item->id)->where('action', 'updated')->exists())->toBeTrue();

    Notification::assertSentTo($admin, KitItemFlaggedForInspection::class);
});

it('removes an inspection flag through the dedicated destroy route', function () {
    [$client, $user, $kitType] = makePortalSetup('unflag');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'UNFLAG-001',
        'status' => 'in_service',
        'flagged_for_inspection' => true,
        'flag_notes' => 'No longer needed',
    ]);

    $this->actingAs($user)
        ->delete(route('portal.kit.flag.destroy', $item))
        ->assertRedirect(route('portal.kit.show', $item));

    $item->refresh();

    expect($item->flagged_for_inspection)->toBeFalse();
    expect($item->flag_notes)->toBeNull();
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

    $this->actingAs($user)->get(route('portal.kit.show', $item));

    $this->actingAs($user)
        ->patch(route('portal.kit.retire', $item), ['confirmation_phrase' => "RETIRE-ITEM-{$item->id}"])
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
        ->post(route('portal.kit.flag', $otherItem))
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

it('allows a client viewer to submit a kit item with a custom type name', function () {
    [$client, $user] = makePortalSetup('custom');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'custom_type_name' => 'Singing Rock Roof Master',
            'asset_tag' => 'CUSTOM-001',
        ])
        ->assertRedirect(route('portal.kit.index'));

    $item = KitItem::where('asset_tag', 'CUSTOM-001')->firstOrFail();

    expect($item->kit_type_id)->toBeNull();
    expect($item->custom_type_name)->toBe('Singing Rock Roof Master');
    expect($item->pending_review)->toBeTrue();
    expect($item->typeName())->toBe('Singing Rock Roof Master');
    expect($item->isCustomType())->toBeTrue();
});

it('rejects a submission where neither kit_type_id nor custom_type_name is provided', function () {
    [, $user] = makePortalSetup('notype');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), ['asset_tag' => 'FAIL-001'])
        ->assertSessionHasErrors(['kit_type_id', 'custom_type_name']);
});

it('allows a client viewer to update the custom name while pending', function () {
    [$client, $user] = makePortalSetup('editcustom');

    $item = $client->kitItems()->create([
        'kit_type_id' => null,
        'custom_type_name' => 'Old Name',
        'asset_tag' => 'EDIT-001',
        'status' => 'in_service',
        'pending_review' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('portal.kit.updateCustomName', $item), ['custom_type_name' => 'New Corrected Name'])
        ->assertRedirect(route('portal.kit.show', $item));

    expect($item->refresh()->custom_type_name)->toBe('New Corrected Name');
});

it('includes custom type items in the admin dashboard unassigned section', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    [$client] = makePortalSetup('customdash');

    $client->kitItems()->create([
        'kit_type_id' => null,
        'custom_type_name' => 'Mystery Harness X1',
        'status' => 'in_service',
        'asset_tag' => 'MYST-001',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Mystery Harness X1');
});

it('submits a kit item assigned to an existing group', function () {
    [$client, $user, $kitType] = makePortalSetup('grp-existing');
    $group = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Personal Set']);

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'kit_type_id' => $kitType->id,
            'asset_tag' => 'GRP-EXIST-001',
            'kit_group_id' => $group->id,
        ])
        ->assertRedirect(route('portal.kit.index'));

    $item = KitItem::where('asset_tag', 'GRP-EXIST-001')->firstOrFail();
    expect($item->kit_group_id)->toBe($group->id);
});

it('submits a kit item and creates a new group inline', function () {
    [$client, $user, $kitType] = makePortalSetup('grp-new');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'kit_type_id' => $kitType->id,
            'asset_tag' => 'GRP-NEW-001',
            'new_group_name' => 'Bag A',
        ])
        ->assertRedirect(route('portal.kit.index'));

    $group = KitGroup::where('client_id', $client->id)->where('name', 'Bag A')->firstOrFail();
    $item = KitItem::where('asset_tag', 'GRP-NEW-001')->firstOrFail();
    expect($item->kit_group_id)->toBe($group->id);

    expect(AuditLog::where('subject_type', 'KitGroup')->where('subject_id', $group->id)->where('action', 'created')->exists())->toBeTrue();
    expect(AuditLog::where('subject_type', 'KitItem')->where('subject_id', $item->id)->where('action', 'created')->exists())->toBeTrue();
});

it('rejects a kit_group_id belonging to another client', function () {
    [, $userA, $kitTypeA] = makePortalSetup('grp-xa');
    [$clientB] = makePortalSetup('grp-xb');
    $otherGroup = KitGroup::factory()->create(['client_id' => $clientB->id]);

    $this->actingAs($userA)
        ->post(route('portal.kit.store'), [
            'kit_type_id' => $kitTypeA->id,
            'asset_tag' => 'GRP-X-001',
            'kit_group_id' => $otherGroup->id,
        ])
        ->assertSessionHasErrors('kit_group_id');

    expect(KitItem::where('asset_tag', 'GRP-X-001')->exists())->toBeFalse();
});

it('shows a non-review flash for an all-known-types submission', function () {
    [, $user, $kitType] = makePortalSetup('flash-known');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'kit_type_id' => $kitType->id,
            'asset_tag' => 'KNOWN-1',
        ])
        ->assertRedirect(route('portal.kit.index'))
        ->assertSessionHas('success', fn (string $msg) => str_contains($msg, 'added')
            && ! str_contains(strtolower($msg), 'review'));
});

it('shows a review flash when any submitted item is custom', function () {
    [, $user] = makePortalSetup('flash-custom');

    $this->actingAs($user)
        ->post(route('portal.kit.store'), [
            'custom_type_name' => 'Roof Master Harness',
            'asset_tag' => 'CUST-1',
        ])
        ->assertRedirect(route('portal.kit.index'))
        ->assertSessionHas('success', fn (string $msg) => str_contains(strtolower($msg), 'review')
            && str_contains(strtolower($msg), '1 working day'));
});
