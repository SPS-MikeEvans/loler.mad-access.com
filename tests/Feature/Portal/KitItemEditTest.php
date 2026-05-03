<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Inspection;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

function makeEditSetup(string $suffix = ''): array
{
    $client = Client::create([
        'name' => 'Edit Client '.$suffix,
        'address' => '1 Edit Street',
        'contact_email' => 'edit'.$suffix.'@test.com',
        'phone' => '01234567890',
    ]);

    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    $kitType = KitType::create(['name' => 'Edit Rope '.$suffix, 'interval_months' => 6]);

    return [$client, $user, $kitType];
}

it('shows the edit form with all fields editable when no inspections exist', function () {
    [$client, $user, $kitType] = makeEditSetup('open');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'E-1',
        'manufacturer' => 'Petzl',
        'status' => 'in_service',
    ]);

    $response = $this->actingAs($user)->get(route('portal.kit.edit', $item));
    $response->assertOk();
    $response->assertDontSee('disabled', false); // identity inputs not disabled
});

it('disables identity fields when an inspection exists', function () {
    [$client, $user, $kitType] = makeEditSetup('locked');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'E-2',
        'manufacturer' => 'Petzl',
        'model' => 'Arial',
        'serial_no' => 'SN-LOCK',
        'status' => 'in_service',
    ]);

    Inspection::create([
        'kit_item_id' => $item->id,
        'inspector_user_id' => $user->id,
        'status' => 'complete',
        'inspection_date' => now()->subWeek(),
        'next_due_date' => now()->addMonths(6),
        'overall_status' => 'pass',
    ]);

    $response = $this->actingAs($user)->get(route('portal.kit.edit', $item));
    $response->assertOk();
    $response->assertSee('disabled', false);
    $response->assertSee('Some fields are locked');
});

it('updates unlocked fields successfully when locked', function () {
    [$client, $user, $kitType] = makeEditSetup('updlock');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'E-3',
        'manufacturer' => 'Petzl',
        'model' => 'Arial',
        'serial_no' => 'SN-LOCKED',
        'status' => 'in_service',
        'swl_kg' => 100,
    ]);

    Inspection::create([
        'kit_item_id' => $item->id,
        'inspector_user_id' => $user->id,
        'status' => 'complete',
        'inspection_date' => now()->subWeek(),
        'next_due_date' => now()->addMonths(6),
        'overall_status' => 'pass',
    ]);

    $this->actingAs($user)
        ->patch(route('portal.kit.update', $item), [
            'swl_kg' => 200,
            'flag_notes' => 'Used last weekend',
        ])
        ->assertRedirect(route('portal.kit.show', $item));

    $item->refresh();
    expect($item->swl_kg)->toBe(200);
    expect($item->flag_notes)->toBe('Used last weekend');
    expect($item->serial_no)->toBe('SN-LOCKED'); // unchanged
});

it('drops smuggled locked fields when inspections exist', function () {
    [$client, $user, $kitType] = makeEditSetup('smuggle');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'E-4',
        'manufacturer' => 'Petzl',
        'model' => 'Arial',
        'serial_no' => 'SN-ORIG',
        'status' => 'in_service',
    ]);

    Inspection::create([
        'kit_item_id' => $item->id,
        'inspector_user_id' => $user->id,
        'status' => 'complete',
        'inspection_date' => now()->subWeek(),
        'next_due_date' => now()->addMonths(6),
        'overall_status' => 'pass',
    ]);

    $this->actingAs($user)
        ->patch(route('portal.kit.update', $item), [
            'swl_kg' => 150,
            'serial_no' => 'HACKED',
            'manufacturer' => 'Hacked Co',
        ])
        ->assertRedirect();

    $item->refresh();
    expect($item->serial_no)->toBe('SN-ORIG');
    expect($item->manufacturer)->toBe('Petzl');
    expect($item->swl_kg)->toBe(150);
});

it('accepts locked fields when no inspections exist', function () {
    [$client, $user, $kitType] = makeEditSetup('unlocked');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'E-5',
        'manufacturer' => 'Old',
        'status' => 'in_service',
    ]);

    $this->actingAs($user)
        ->patch(route('portal.kit.update', $item), [
            'manufacturer' => 'New',
            'model' => 'Updated',
            'serial_no' => 'SN-NEW',
        ])
        ->assertRedirect();

    $item->refresh();
    expect($item->manufacturer)->toBe('New');
    expect($item->model)->toBe('Updated');
    expect($item->serial_no)->toBe('SN-NEW');
});

it('forbids cross-client update', function () {
    [, $userA, $kitType] = makeEditSetup('xa');
    [$clientB] = makeEditSetup('xb');

    $other = KitItem::create([
        'client_id' => $clientB->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'X-1',
        'status' => 'in_service',
    ]);

    $this->actingAs($userA)
        ->patch(route('portal.kit.update', $other), ['swl_kg' => 999])
        ->assertForbidden();

    expect($other->refresh()->swl_kg)->not->toBe(999);
});

it('writes an audit log row with changed fields on update', function () {
    [$client, $user, $kitType] = makeEditSetup('audit');

    $item = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'A-1',
        'status' => 'in_service',
    ]);

    $this->actingAs($user)
        ->patch(route('portal.kit.update', $item), ['swl_kg' => 50, 'flag_notes' => 'note'])
        ->assertRedirect();

    $audit = AuditLog::where('subject_type', 'KitItem')
        ->where('subject_id', $item->id)
        ->where('action', 'updated')
        ->latest('created_at')
        ->first();
    expect($audit)->not->toBeNull();
    expect($audit->metadata['changed_fields'])->toContain('swl_kg');
});
