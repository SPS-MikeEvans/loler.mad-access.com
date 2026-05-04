<?php

use App\Models\Client;
use App\Models\Inspection;
use App\Models\InspectionCheck;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function makeCostFlowUser(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'competent_person_flag' => true,
    ]);
}

function makeCostFlowKit(float $price = 37.50): array
{
    $client = Client::factory()->create();
    $kitType = KitType::create([
        'name' => 'Cost Flow Harness',
        'interval_months' => 6,
        'inspection_price' => $price,
    ]);
    $kitItem = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'COST-'.fake()->unique()->numberBetween(1000, 9999),
        'serial_no' => 'SN-COST-'.fake()->unique()->numberBetween(1000, 9999),
        'status' => 'inspection_due',
    ]);

    return [$client, $kitType, $kitItem];
}

it('does not render costs or totals in the client examination report view', function () {
    $inspector = makeCostFlowUser();
    [$client, , $kitItem] = makeCostFlowKit(44.25);

    $inspection = Inspection::create([
        'kit_item_id' => $kitItem->id,
        'inspector_user_id' => $inspector->id,
        'status' => 'complete',
        'inspection_date' => '2026-05-04',
        'next_due_date' => '2026-11-04',
        'overall_status' => 'pass',
        'cost' => 44.25,
    ]);

    $html = view('reports.client-inspections', [
        'client' => $client,
        'inspections' => collect([['item' => $kitItem->load('kitType'), 'inspection' => $inspection->load('inspector')]]),
        'year' => 2026,
        'month' => 5,
    ])->render();

    expect($html)
        ->not->toContain('Cost (£)')
        ->not->toContain('total-row')
        ->not->toContain('Total')
        ->toContain('LOLER Thorough Examination Report')
        ->toContain('Cost Flow Harness');
});

it('keeps desktop inspection creation costed from the kit type price', function () {
    $admin = makeCostFlowUser();
    [$client, , $kitItem] = makeCostFlowKit(52.75);

    $this->actingAs($admin)
        ->post(route('clients.kit-items.inspections.store', [$client, $kitItem]), [
            'inspection_date' => '2026-05-04',
            'next_due_date' => '2026-11-04',
            'overall_status' => 'pass',
            'checks' => [
                [
                    'check_category' => 'Hardware',
                    'check_text' => 'No visible damage',
                    'status' => 'pass',
                ],
            ],
        ])
        ->assertRedirect(route('clients.kit-items.show', [$client, $kitItem]));

    expect((float) $kitItem->inspections()->first()->cost)->toBe(52.75);
});

it('sets mobile draft inspection cost from the kit type price', function () {
    $admin = makeCostFlowUser();
    [, , $kitItem] = makeCostFlowKit(31.25);

    $this->actingAs($admin)
        ->post(route('mobile.inspect.create-draft', $kitItem))
        ->assertRedirect();

    $inspection = $kitItem->inspections()->first();

    expect($inspection->status)->toBe('draft');
    expect((float) $inspection->cost)->toBe(31.25);
});

it('backfills an older mobile draft with a null cost when completing it', function () {
    Storage::fake('public');

    $admin = makeCostFlowUser();
    [, , $kitItem] = makeCostFlowKit(28.00);
    $inspection = Inspection::create([
        'kit_item_id' => $kitItem->id,
        'inspector_user_id' => $admin->id,
        'status' => 'draft',
        'started_at' => now(),
        'inspection_date' => now()->toDateString(),
        'next_due_date' => now()->addMonths(6)->toDateString(),
        'overall_status' => 'pass',
        'cost' => null,
    ]);
    InspectionCheck::create([
        'inspection_id' => $inspection->id,
        'check_category' => 'Hardware',
        'check_text' => 'No visible damage',
        'status' => 'pass',
    ]);

    $this->actingAs($admin)
        ->post(route('mobile.inspect.complete', $inspection), [
            'report_notes' => 'Completed from old draft.',
        ])
        ->assertRedirect(route('mobile.inspect.done', $inspection));

    expect((float) $inspection->refresh()->cost)->toBe(28.00);
});

it('does not overwrite an existing mobile draft cost when completing it', function () {
    Storage::fake('public');

    $admin = makeCostFlowUser();
    [, , $kitItem] = makeCostFlowKit(28.00);
    $inspection = Inspection::create([
        'kit_item_id' => $kitItem->id,
        'inspector_user_id' => $admin->id,
        'status' => 'draft',
        'started_at' => now(),
        'inspection_date' => now()->toDateString(),
        'next_due_date' => now()->addMonths(6)->toDateString(),
        'overall_status' => 'pass',
        'cost' => 19.50,
    ]);
    InspectionCheck::create([
        'inspection_id' => $inspection->id,
        'check_category' => 'Hardware',
        'check_text' => 'No visible damage',
        'status' => 'pass',
    ]);

    $this->actingAs($admin)
        ->post(route('mobile.inspect.complete', $inspection))
        ->assertRedirect(route('mobile.inspect.done', $inspection));

    expect((float) $inspection->refresh()->cost)->toBe(19.50);
});

it('costs block-created kit when inspected through the mobile wizard', function () {
    $admin = makeCostFlowUser();
    $client = Client::factory()->create();
    $kitType = KitType::create([
        'name' => 'Block Cost Carabiner',
        'interval_months' => 6,
        'inspection_price' => 12.40,
    ]);

    $this->actingAs($admin)
        ->post(route('clients.kit-items.store', $client), [
            'kit_type_id' => $kitType->id,
            'quantity' => 3,
            'asset_tag_prefix' => 'BC-',
            'asset_tag_start' => 1,
            'asset_tag_padding' => 2,
            'status' => 'in_service',
        ])
        ->assertRedirect(route('clients.kit-items.index', $client));

    $kitItem = KitItem::where('client_id', $client->id)
        ->where('asset_tag', 'BC-01')
        ->firstOrFail();

    $this->actingAs($admin)
        ->post(route('mobile.inspect.create-draft', $kitItem))
        ->assertRedirect();

    expect((float) $kitItem->inspections()->first()->cost)->toBe(12.40);
});
