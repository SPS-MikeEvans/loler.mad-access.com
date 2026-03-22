<?php

use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitTypeRefreshComplete;
use App\Services\KitTypeAiRefreshService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function fakeXaiResponse(array $equipment): array
{
    return [
        'choices' => [[
            'message' => [
                'content' => json_encode(['equipment' => $equipment]),
            ],
        ]],
    ];
}

function fakeAllBrands(array $equipment = []): void
{
    Http::fake(['api.x.ai/*' => Http::response(fakeXaiResponse($equipment), 200)]);
}

it('runs the refresh and redirects admin with summary', function () {
    fakeAllBrands();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('kit-types.ai-refresh'))
        ->assertRedirect(route('kit-types.index'))
        ->assertSessionHas('success');
});

it('forbids inspectors from triggering the refresh', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)
        ->post(route('kit-types.ai-refresh'))
        ->assertForbidden();
});

it('adds new kit types with ai_suggested flag and skips existing ones', function () {
    Http::fake([
        'api.x.ai/*' => Http::response(fakeXaiResponse([
            ['name' => 'Brand New Harness X', 'category' => 'Harness', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => '140 kg', 'inspection_price_gbp' => 45.0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
            ['name' => 'Existing Harness', 'category' => 'Harness', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => '100 kg', 'inspection_price_gbp' => 50.0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
        ]), 200),
    ]);

    KitType::create(['name' => 'Existing Harness', 'brand' => 'Petzl', 'category' => 'Connector', 'interval_months' => 12]);

    app(KitTypeAiRefreshService::class)->run();

    $new = KitType::where('name', 'Brand New Harness X')->where('brand', 'Petzl')->firstOrFail();
    expect($new->ai_suggested)->toBeTrue();

    expect(KitType::where('name', 'Existing Harness')->where('brand', 'Petzl')->value('category'))->toBe('Connector');
});

it('skips items with invalid names', function () {
    Http::fake([
        'api.x.ai/*' => Http::response(fakeXaiResponse([
            ['name' => 'ab', 'category' => 'Other', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => '', 'inspection_price_gbp' => 0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
        ]), 200),
    ]);

    app(KitTypeAiRefreshService::class)->run();

    expect(KitType::where('name', 'ab')->exists())->toBeFalse();
});

it('stores completed totals in cache and sends admin notification', function () {
    Notification::fake();
    fakeAllBrands();

    $admin = User::factory()->create(['role' => 'admin']);

    $totals = app(KitTypeAiRefreshService::class)->run();

    expect($totals['done'])->toBe(count(KitTypeAiRefreshService::BRANDS));
    expect(cache()->get('kit_types.refresh_total')['done'])->toBe(count(KitTypeAiRefreshService::BRANDS));

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
