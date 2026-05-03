<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\KitGroup;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeGroupSetup(string $suffix = ''): array
{
    $client = Client::create([
        'name' => 'Group Test Client '.$suffix,
        'address' => '1 Group Street',
        'contact_email' => 'group'.$suffix.'@test.com',
        'phone' => '01234567890',
    ]);

    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    $kitType = KitType::create(['name' => 'Group Rope '.$suffix, 'interval_months' => 6]);

    return [$client, $user, $kitType];
}

it('lists only the auth client groups on the index', function () {
    [$clientA, $userA] = makeGroupSetup('A');
    [$clientB] = makeGroupSetup('B');

    $myGroup = KitGroup::factory()->create(['client_id' => $clientA->id, 'name' => 'My Personal Set']);
    $otherGroup = KitGroup::factory()->create(['client_id' => $clientB->id, 'name' => 'Their Set']);

    $this->actingAs($userA)
        ->get(route('portal.kit-groups.index'))
        ->assertOk()
        ->assertSee('My Personal Set')
        ->assertDontSee('Their Set');
});

it('forbids viewing another clients group', function () {
    [, $userA] = makeGroupSetup('SA');
    [$clientB] = makeGroupSetup('SB');
    $group = KitGroup::factory()->create(['client_id' => $clientB->id]);

    $this->actingAs($userA)
        ->get(route('portal.kit-groups.show', $group))
        ->assertForbidden();
});

it('creates a group and assigns submitted items with a single audit row', function () {
    [$client, $user, $kitType] = makeGroupSetup('create');

    $a = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'GA-1', 'status' => 'in_service']);
    $b = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'GA-2', 'status' => 'in_service']);

    $this->actingAs($user)
        ->post(route('portal.kit-groups.store'), [
            'name' => 'Carabiners — Bag A',
            'description' => 'Bag of 10',
            'kit_item_ids' => [$a->id, $b->id],
        ])
        ->assertRedirect();

    $group = KitGroup::where('name', 'Carabiners — Bag A')->firstOrFail();
    expect($group->client_id)->toBe($client->id);

    expect($a->refresh()->kit_group_id)->toBe($group->id);
    expect($b->refresh()->kit_group_id)->toBe($group->id);

    $auditCount = AuditLog::where('subject_type', 'KitGroup')
        ->where('subject_id', $group->id)
        ->where('action', 'created')
        ->count();
    expect($auditCount)->toBe(1);

    $audit = AuditLog::where('subject_type', 'KitGroup')->where('subject_id', $group->id)->first();
    expect($audit->metadata['kit_item_ids_attached'])->toEqual([$a->id, $b->id]);
});

it('rejects kit_item_ids belonging to another client', function () {
    [$clientA, $userA, $kitType] = makeGroupSetup('reject-a');
    [$clientB] = makeGroupSetup('reject-b');

    $other = KitItem::create(['client_id' => $clientB->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'OTHER-1', 'status' => 'in_service']);

    $this->actingAs($userA)
        ->post(route('portal.kit-groups.store'), [
            'name' => 'Hijack',
            'kit_item_ids' => [$other->id],
        ])
        ->assertSessionHasErrors('kit_item_ids.0');

    expect(KitGroup::where('name', 'Hijack')->exists())->toBeFalse();
    expect($other->refresh()->kit_group_id)->toBeNull();
});

it('updates a group reassigning items in and out', function () {
    [$client, $user, $kitType] = makeGroupSetup('update');

    $a = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'U-1', 'status' => 'in_service']);
    $b = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'U-2', 'status' => 'in_service']);
    $c = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'U-3', 'status' => 'in_service']);

    $group = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Alpha']);
    $a->update(['kit_group_id' => $group->id]);
    $b->update(['kit_group_id' => $group->id]);

    $this->actingAs($user)
        ->patch(route('portal.kit-groups.update', $group), [
            'name' => 'Alpha Renamed',
            'kit_item_ids' => [$b->id, $c->id],
        ])
        ->assertRedirect(route('portal.kit-groups.show', $group));

    expect($group->refresh()->name)->toBe('Alpha Renamed');
    expect($a->refresh()->kit_group_id)->toBeNull();
    expect($b->refresh()->kit_group_id)->toBe($group->id);
    expect($c->refresh()->kit_group_id)->toBe($group->id);

    $audit = AuditLog::where('subject_type', 'KitGroup')->where('subject_id', $group->id)->where('action', 'updated')->first();
    expect($audit)->not->toBeNull();
    expect($audit->metadata['kit_item_ids_detached'])->toEqual([$a->id]);
    expect($audit->metadata['kit_item_ids_attached'])->toEqual([$c->id]);
});

it('deletes a group with valid confirmation phrase, detaching items', function () {
    [$client, $user, $kitType] = makeGroupSetup('delete');

    $group = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'ToDelete']);
    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_group_id' => $group->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'D-1',
        'status' => 'in_service',
    ]);

    // Issue confirmation by visiting edit
    $this->actingAs($user)->get(route('portal.kit-groups.edit', $group))->assertOk();

    $this->actingAs($user)
        ->delete(route('portal.kit-groups.destroy', $group), [
            'confirmation_phrase' => "DELETE-GROUP-{$group->id}",
        ])
        ->assertRedirect(route('portal.kit-groups.index'));

    expect($group->fresh()->trashed())->toBeTrue();
    expect($item->refresh()->kit_group_id)->toBeNull();

    $audit = AuditLog::where('subject_type', 'KitGroup')->where('subject_id', $group->id)->where('action', 'deleted')->first();
    expect($audit)->not->toBeNull();
    expect($audit->metadata['confirmation_phrase'])->toBe("DELETE-GROUP-{$group->id}");
});

it('rejects delete without confirmation phrase', function () {
    [$client, $user] = makeGroupSetup('noconfirm');
    $group = KitGroup::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user)->get(route('portal.kit-groups.edit', $group));

    $this->actingAs($user)
        ->delete(route('portal.kit-groups.destroy', $group), [
            'confirmation_phrase' => 'WRONG',
        ])
        ->assertSessionHasErrors('confirmation_phrase');

    expect($group->fresh()->trashed())->toBeFalse();
});

it('rejects a replayed confirmation token after the group is gone', function () {
    [$client, $user] = makeGroupSetup('replay');
    $group = KitGroup::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user)->get(route('portal.kit-groups.edit', $group));
    $this->actingAs($user)->delete(route('portal.kit-groups.destroy', $group), [
        'confirmation_phrase' => "DELETE-GROUP-{$group->id}",
    ])->assertRedirect();

    // Second attempt: the group is now soft-deleted, so route binding returns 404.
    // The token has also been consumed so a fresh group cannot be deleted with it either.
    $this->actingAs($user)
        ->delete(route('portal.kit-groups.destroy', $group), [
            'confirmation_phrase' => "DELETE-GROUP-{$group->id}",
        ])
        ->assertNotFound();
});

it('does not consume a token on a failed (wrong-phrase) attempt', function () {
    [$client, $user] = makeGroupSetup('preserve');
    $group = KitGroup::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user)->get(route('portal.kit-groups.edit', $group));

    // Wrong phrase first
    $this->actingAs($user)
        ->delete(route('portal.kit-groups.destroy', $group), ['confirmation_phrase' => 'WRONG'])
        ->assertSessionHasErrors('confirmation_phrase');

    expect($group->fresh()->trashed())->toBeFalse();

    // Correct phrase should now succeed (token still valid)
    $this->actingAs($user)
        ->delete(route('portal.kit-groups.destroy', $group), [
            'confirmation_phrase' => "DELETE-GROUP-{$group->id}",
        ])
        ->assertRedirect();

    expect($group->fresh()->trashed())->toBeTrue();
});

it('forbids cross-client destroy', function () {
    [, $userA] = makeGroupSetup('xa');
    [$clientB] = makeGroupSetup('xb');
    $group = KitGroup::factory()->create(['client_id' => $clientB->id]);

    $this->actingAs($userA)
        ->delete(route('portal.kit-groups.destroy', $group), [
            'confirmation_phrase' => "DELETE-GROUP-{$group->id}",
        ])
        ->assertForbidden();
});
