<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\KitGroup;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeAdminGroupSetup(string $suffix = ''): array
{
    $client = Client::factory()->create([
        'name' => 'Admin Group Client '.$suffix,
        'contact_email' => 'admin-group-'.$suffix.'@example.com',
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $inspector = User::factory()->create(['role' => 'inspector']);
    $kitType = KitType::create(['name' => 'Admin Group Rope '.$suffix, 'interval_months' => 6]);

    return [$client, $admin, $inspector, $kitType];
}

it('allows back-office users to list client kit groups', function () {
    [$client, , $inspector] = makeAdminGroupSetup('list');
    $group = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Shared Rescue Set']);

    $this->actingAs($inspector)
        ->get(route('clients.kit-groups.index', $client))
        ->assertOk()
        ->assertSee('Shared Rescue Set');
});

it('creates a client kit group and assigns submitted items', function () {
    [$client, $admin, , $kitType] = makeAdminGroupSetup('create');

    $a = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'AG-1', 'status' => 'in_service']);
    $b = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'AG-2', 'status' => 'in_service']);

    $this->actingAs($admin)
        ->post(route('clients.kit-groups.store', $client), [
            'name' => 'Bag A',
            'description' => 'Main climbing kit',
            'kit_item_ids' => [$a->id, $b->id],
        ])
        ->assertRedirect();

    $group = KitGroup::where('client_id', $client->id)->where('name', 'Bag A')->firstOrFail();

    expect($a->refresh()->kit_group_id)->toBe($group->id);
    expect($b->refresh()->kit_group_id)->toBe($group->id);
    expect(AuditLog::where('subject_type', 'KitGroup')->where('subject_id', $group->id)->where('action', 'created')->exists())->toBeTrue();
});

it('rejects submitted kit items from another client', function () {
    [$clientA, $admin, , $kitType] = makeAdminGroupSetup('reject-a');
    [$clientB] = makeAdminGroupSetup('reject-b');

    $other = KitItem::create(['client_id' => $clientB->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'OTHER', 'status' => 'in_service']);

    $this->actingAs($admin)
        ->post(route('clients.kit-groups.store', $clientA), [
            'name' => 'Wrong Client',
            'kit_item_ids' => [$other->id],
        ])
        ->assertSessionHasErrors('kit_item_ids.0');

    expect(KitGroup::where('client_id', $clientA->id)->where('name', 'Wrong Client')->exists())->toBeFalse();
    expect($other->refresh()->kit_group_id)->toBeNull();
});

it('updates a client kit group by attaching and detaching items', function () {
    [$client, , $inspector, $kitType] = makeAdminGroupSetup('update');

    $a = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'AU-1', 'status' => 'in_service']);
    $b = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'AU-2', 'status' => 'in_service']);
    $c = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'AU-3', 'status' => 'in_service']);
    $group = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Old Name']);

    $a->update(['kit_group_id' => $group->id]);
    $b->update(['kit_group_id' => $group->id]);

    $this->actingAs($inspector)
        ->patch(route('clients.kit-groups.update', [$client, $group]), [
            'name' => 'New Name',
            'kit_item_ids' => [$b->id, $c->id],
        ])
        ->assertRedirect(route('clients.kit-groups.show', [$client, $group]));

    expect($group->refresh()->name)->toBe('New Name');
    expect($a->refresh()->kit_group_id)->toBeNull();
    expect($b->refresh()->kit_group_id)->toBe($group->id);
    expect($c->refresh()->kit_group_id)->toBe($group->id);
});

it('deletes a client kit group after confirmation and detaches items', function () {
    [$client, $admin, , $kitType] = makeAdminGroupSetup('delete');

    $group = KitGroup::factory()->create(['client_id' => $client->id, 'name' => 'Delete Me']);
    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_group_id' => $group->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'AD-1',
        'status' => 'in_service',
    ]);

    $this->actingAs($admin)->get(route('clients.kit-groups.edit', [$client, $group]))->assertOk();

    $this->actingAs($admin)
        ->delete(route('clients.kit-groups.destroy', [$client, $group]), [
            'confirmation_phrase' => "DELETE-GROUP-{$group->id}",
        ])
        ->assertRedirect(route('clients.kit-groups.index', $client));

    expect($group->fresh()->trashed())->toBeTrue();
    expect($item->refresh()->kit_group_id)->toBeNull();
    expect(AuditLog::where('subject_type', 'KitGroup')->where('subject_id', $group->id)->where('action', 'deleted')->exists())->toBeTrue();
});

it('forbids client viewers from back-office kit group routes', function () {
    [$client] = makeAdminGroupSetup('viewer');
    $viewer = User::factory()->clientViewer()->create(['client_id' => $client->id]);

    $this->actingAs($viewer)
        ->get(route('clients.kit-groups.index', $client))
        ->assertForbidden();
});
