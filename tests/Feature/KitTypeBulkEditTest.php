<?php

use App\Models\AuditLog;
use App\Models\KitType;
use App\Models\User;

function makeAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('shows the bulk-edit form for selected kit types', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'Type A', 'interval_months' => 6]);
    $b = KitType::create(['name' => 'Type B', 'interval_months' => 6]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.form'), ['kit_type_ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertSee('2 kit types selected')
        ->assertSee('Type A')
        ->assertSee('Type B');
});

it('redirects back when no kit types are selected', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.form'), [])
        ->assertRedirect(route('kit-types.index'))
        ->assertSessionHas('error');
});

it('forbids non-admin users from bulk-edit', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $a = KitType::create(['name' => 'X', 'interval_months' => 6]);

    $this->actingAs($inspector)
        ->post(route('kit-types.bulk-edit.form'), ['kit_type_ids' => [$a->id]])
        ->assertForbidden();
});

it('sets the inspection price absolutely on selected types', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6, 'inspection_price' => 5.00]);
    $b = KitType::create(['name' => 'B', 'interval_months' => 6, 'inspection_price' => null]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'set_price',
            'kit_type_ids' => [$a->id, $b->id],
            'value' => '15.50',
        ])
        ->assertRedirect(route('kit-types.index'));

    expect((float) $a->refresh()->inspection_price)->toBe(15.50);
    expect((float) $b->refresh()->inspection_price)->toBe(15.50);
});

it('adjusts price by amount and clamps to zero', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6, 'inspection_price' => 10.00]);
    $b = KitType::create(['name' => 'B', 'interval_months' => 6, 'inspection_price' => 1.00]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'adjust_price_amount',
            'kit_type_ids' => [$a->id, $b->id],
            'value' => '-5.00',
        ])
        ->assertRedirect();

    expect((float) $a->refresh()->inspection_price)->toBe(5.00);
    expect((float) $b->refresh()->inspection_price)->toBe(0.00); // clamped
});

it('adjusts price by percentage', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6, 'inspection_price' => 10.00]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'adjust_price_percent',
            'kit_type_ids' => [$a->id],
            'value' => '20',
        ])
        ->assertRedirect();

    expect((float) $a->refresh()->inspection_price)->toBe(12.00);
});

it('appends a resource link without overwriting existing ones', function () {
    $admin = makeAdmin();
    $a = KitType::create([
        'name' => 'A',
        'interval_months' => 6,
        'resources_links' => [['name' => 'Existing', 'url' => 'https://existing.example/a']],
    ]);
    $b = KitType::create(['name' => 'B', 'interval_months' => 6, 'resources_links' => null]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'add_resource_link',
            'kit_type_ids' => [$a->id, $b->id],
            'link_name' => 'Datasheet',
            'link_url' => 'https://manuf.example/sheet.pdf',
        ])
        ->assertRedirect();

    $aLinks = $a->refresh()->resources_links;
    expect($aLinks)->toHaveCount(2);
    expect(collect($aLinks)->pluck('url')->all())->toContain('https://existing.example/a', 'https://manuf.example/sheet.pdf');

    $bLinks = $b->refresh()->resources_links;
    expect($bLinks)->toHaveCount(1);
    expect($bLinks[0]['url'])->toBe('https://manuf.example/sheet.pdf');
});

it('skips appending a duplicate URL', function () {
    $admin = makeAdmin();
    $a = KitType::create([
        'name' => 'A',
        'interval_months' => 6,
        'resources_links' => [['name' => 'Existing', 'url' => 'https://example/x']],
    ]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'add_resource_link',
            'kit_type_ids' => [$a->id],
            'link_name' => 'Dup',
            'link_url' => 'https://example/x',
        ])
        ->assertRedirect();

    expect($a->refresh()->resources_links)->toHaveCount(1);
});

it('removes a resource link by URL', function () {
    $admin = makeAdmin();
    $a = KitType::create([
        'name' => 'A',
        'interval_months' => 6,
        'resources_links' => [
            ['name' => 'Keep', 'url' => 'https://keep.example'],
            ['name' => 'Drop', 'url' => 'https://drop.example'],
        ],
    ]);
    $b = KitType::create([
        'name' => 'B',
        'interval_months' => 6,
        'resources_links' => [['name' => 'Drop', 'url' => 'https://drop.example']],
    ]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'remove_resource_link',
            'kit_type_ids' => [$a->id, $b->id],
            'link_url' => 'https://drop.example',
        ])
        ->assertRedirect();

    $aLinks = $a->refresh()->resources_links;
    expect($aLinks)->toHaveCount(1);
    expect($aLinks[0]['url'])->toBe('https://keep.example');

    expect($b->refresh()->resources_links)->toBeNull();
});

it('sets the inspection interval months', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6]);
    $b = KitType::create(['name' => 'B', 'interval_months' => 12]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'set_interval_months',
            'kit_type_ids' => [$a->id, $b->id],
            'value' => '3',
        ])
        ->assertRedirect();

    expect($a->refresh()->interval_months)->toBe(3);
    expect($b->refresh()->interval_months)->toBe(3);
});

it('sets the lifts_people flag', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6, 'lifts_people' => false]);
    $b = KitType::create(['name' => 'B', 'interval_months' => 6, 'lifts_people' => false]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'set_lifts_people',
            'kit_type_ids' => [$a->id, $b->id],
            'value' => '1',
        ])
        ->assertRedirect();

    expect($a->refresh()->lifts_people)->toBeTrue();
    expect($b->refresh()->lifts_people)->toBeTrue();
});

it('writes one audit log row per kit type changed with old/new metadata', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6]);
    $b = KitType::create(['name' => 'B', 'interval_months' => 6]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'set_interval_months',
            'kit_type_ids' => [$a->id, $b->id],
            'value' => '12',
        ])
        ->assertRedirect();

    $logs = AuditLog::where('subject_type', 'KitType')->where('action', 'updated')->get();
    expect($logs)->toHaveCount(2);
    foreach ($logs as $log) {
        expect($log->metadata['bulk_action'])->toBe('set_interval_months');
        expect($log->metadata['field'])->toBe('interval_months');
        expect($log->metadata['old'])->toBe(6);
        expect($log->metadata['new'])->toBe(12);
    }
});

it('skips no-op rows from audit log', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6]);
    $b = KitType::create(['name' => 'B', 'interval_months' => 12]); // already 12

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'set_interval_months',
            'kit_type_ids' => [$a->id, $b->id],
            'value' => '12',
        ])
        ->assertRedirect();

    expect($a->refresh()->interval_months)->toBe(12);

    $logs = AuditLog::where('subject_type', 'KitType')->where('action', 'updated')->get();
    expect($logs)->toHaveCount(1);
    expect($logs->first()->subject_id)->toBe($a->id);
});

it('rejects an invalid action', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'A', 'interval_months' => 6]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'evil_action',
            'kit_type_ids' => [$a->id],
            'value' => '1',
        ])
        ->assertSessionHasErrors('action');
});

it('rejects when kit_type_ids is empty on apply', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.apply'), [
            'action' => 'set_interval_months',
            'value' => '6',
        ])
        ->assertSessionHasErrors('kit_type_ids');
});

it('shows the preview screen with old and new values', function () {
    $admin = makeAdmin();
    $a = KitType::create(['name' => 'PreviewA', 'interval_months' => 6, 'inspection_price' => 5.00]);

    $this->actingAs($admin)
        ->post(route('kit-types.bulk-edit.preview'), [
            'action' => 'set_price',
            'kit_type_ids' => [$a->id],
            'value' => '12.50',
        ])
        ->assertOk()
        ->assertSee('PreviewA')
        ->assertSee('£5.00')
        ->assertSee('£12.50');
});
