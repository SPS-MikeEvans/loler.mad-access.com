<?php

use App\Jobs\RefreshKitTypesFromAI;
use App\Jobs\RefreshSingleBrandJob;
use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitTypeRefreshComplete;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

it('dispatches the refresh job when admin posts to ai-refresh', function () {
    Bus::fake();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('kit-types.ai-refresh'))
        ->assertRedirect(route('kit-types.index'));

    Bus::assertDispatched(RefreshKitTypesFromAI::class);
});

it('forbids inspectors from triggering the refresh', function () {
    Bus::fake();
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)
        ->post(route('kit-types.ai-refresh'))
        ->assertForbidden();

    Bus::assertNothingDispatched();
});

it('adds new kit types with ai_suggested flag and skips existing ones', function () {
    Http::fake([
        'api.x.ai/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'equipment' => [
                            ['name' => 'Brand New Harness X', 'category' => 'Harness', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => '140 kg', 'inspection_price_gbp' => 45.0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
                            ['name' => 'Existing Harness', 'category' => 'Harness', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => '100 kg', 'inspection_price_gbp' => 50.0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
                        ],
                    ]),
                ],
            ]],
        ], 200),
    ]);

    KitType::create(['name' => 'Existing Harness', 'brand' => 'Petzl', 'category' => 'Connector', 'interval_months' => 12]);

    (new RefreshSingleBrandJob('Petzl'))->handle();

    $new = KitType::where('name', 'Brand New Harness X')->firstOrFail();
    expect($new->ai_suggested)->toBeTrue();
    expect($new->brand)->toBe('Petzl');

    expect(KitType::where('name', 'Existing Harness')->value('category'))->toBe('Connector');
});

it('skips items with invalid names', function () {
    Http::fake([
        'api.x.ai/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'equipment' => [
                            ['name' => 'ab', 'category' => 'Other', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => '', 'inspection_price_gbp' => 0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
                        ],
                    ]),
                ],
            ]],
        ], 200),
    ]);

    (new RefreshSingleBrandJob('Petzl'))->handle();

    expect(KitType::where('name', 'ab')->exists())->toBeFalse();
});

it('stores progress in cache and sends admin notification when all brands done', function () {
    Notification::fake();
    Http::fake([
        'api.x.ai/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode(['equipment' => []]),
                ],
            ]],
        ], 200),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    Cache::put('kit_types.refresh_total', [
        'dispatched' => 1,
        'done' => 0,
        'added' => 0,
        'skipped' => 0,
        'errors' => [],
        'started_at' => now()->toIso8601String(),
    ], now()->addDay());

    (new RefreshSingleBrandJob('Petzl'))->handle();

    $result = Cache::get('kit_types.refresh_total');
    expect($result['done'])->toBe(1);

    Notification::assertSentTo($admin, KitTypeRefreshComplete::class);
});

it('clears ai_suggested flag when admin updates a kit type', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $kitType = KitType::create(['name' => 'AI Rope', 'brand' => 'Petzl', 'interval_months' => 6, 'ai_suggested' => true]);

    $this->actingAs($admin)
        ->patch(route('kit-types.update', $kitType), [
            'name' => 'AI Rope',
            'interval_months' => 6,
        ])
        ->assertRedirect();

    expect($kitType->refresh()->ai_suggested)->toBeFalse();
});
