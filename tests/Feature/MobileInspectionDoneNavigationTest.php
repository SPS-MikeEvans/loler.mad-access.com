<?php

use App\Models\Client;
use App\Models\Inspection;
use App\Models\InspectionCheck;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

it('shows a back to kit list link filtered to inspection due on the mobile inspection done page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();
    $kitType = KitType::create(['name' => 'Done Page Rope', 'interval_months' => 6]);
    $kitItem = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'DONE-1',
        'status' => 'in_service',
    ]);

    $inspection = Inspection::create([
        'kit_item_id' => $kitItem->id,
        'inspector_user_id' => $admin->id,
        'status' => 'complete',
        'inspection_date' => now()->toDateString(),
        'next_due_date' => now()->addMonths(6)->toDateString(),
        'overall_status' => 'pass',
    ]);

    InspectionCheck::create([
        'inspection_id' => $inspection->id,
        'check_category' => 'Hardware',
        'check_text' => 'Gate operates correctly',
        'status' => 'pass',
    ]);

    $this->actingAs($admin)
        ->get(route('mobile.inspect.done', $inspection))
        ->assertOk()
        ->assertSee('Back to Kit List')
        ->assertSee('/clients/'.$client->id.'/kit-items', false)
        ->assertSee('status=inspection_due', false);
});
