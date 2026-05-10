<?php

use App\Models\KitBrand;
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

function activateOnlyBrand(string $name): void
{
    KitBrand::query()->update(['is_active' => false]);
    KitBrand::firstOrCreate(['name' => $name], ['is_active' => true]);
    KitBrand::where('name', $name)->update(['is_active' => true]);
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
    activateOnlyBrand('Petzl');

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
    activateOnlyBrand('Petzl');

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

    $expected = KitBrand::active()->count();
    expect($totals['done'])->toBe($expected);
    expect(cache()->get('kit_types.refresh_total')['done'])->toBe($expected);

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

it('skips inactive brands', function () {
    KitBrand::query()->update(['is_active' => false]);
    KitBrand::firstOrCreate(['name' => 'Petzl'])->update(['is_active' => false]);
    KitBrand::firstOrCreate(['name' => 'DMM Professional'])->update(['is_active' => true]);

    Http::fake([
        'api.x.ai/*' => Http::response(fakeXaiResponse([
            ['name' => 'Active Connector', 'category' => 'Connector', 'interval_months' => 6, 'lifts_people' => false, 'swl_description' => '', 'inspection_price_gbp' => 0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
        ]), 200),
    ]);

    app(KitTypeAiRefreshService::class)->run();

    expect(KitType::where('name', 'Active Connector')->where('brand', 'DMM Professional')->exists())->toBeTrue();
    expect(KitType::where('brand', 'Petzl')->exists())->toBeFalse();
});

it('backfills blank fields on existing ai_suggested rows', function () {
    activateOnlyBrand('Petzl');

    Http::fake([
        'api.x.ai/*' => Http::response(fakeXaiResponse([
            ['name' => 'Sparse AI Item', 'category' => 'Harness', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Max 140 kg', 'inspection_price_gbp' => 55.0, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
        ]), 200),
    ]);

    KitType::create([
        'name' => 'Sparse AI Item',
        'brand' => 'Petzl',
        'interval_months' => 6,
        'ai_suggested' => true,
        'swl_description' => null,
        'category' => null,
    ]);

    $totals = app(KitTypeAiRefreshService::class)->run();

    $row = KitType::where('name', 'Sparse AI Item')->where('brand', 'Petzl')->firstOrFail();
    expect($row->swl_description)->toBe('Max 140 kg');
    expect($row->category)->toBe('Harness');
    expect($totals['updated'])->toBeGreaterThan(0);
});

it('does not overwrite admin-edited rows on refresh', function () {
    activateOnlyBrand('Petzl');

    Http::fake([
        'api.x.ai/*' => Http::response(fakeXaiResponse([
            ['name' => 'Hand-edited Harness', 'category' => 'Harness', 'interval_months' => 6, 'lifts_people' => true, 'swl_description' => 'Robot text', 'inspection_price_gbp' => 99.99, 'technical_pdf_url' => null, 'instructions_pdf_url' => null],
        ]), 200),
    ]);

    $kitType = KitType::create([
        'name' => 'Hand-edited Harness',
        'brand' => 'Petzl',
        'category' => 'Harness',
        'interval_months' => 12,
        'swl_description' => 'Curated by hand',
        'inspection_price' => 33.33,
        'ai_suggested' => false,
    ]);

    app(KitTypeAiRefreshService::class)->run();

    $row = $kitType->refresh();
    expect($row->swl_description)->toBe('Curated by hand');
    expect((float) $row->inspection_price)->toBe(33.33);
    expect($row->interval_months)->toBe(12);
});

it('passes existing kit type names to the gap-fill pass', function () {
    activateOnlyBrand('Petzl');

    KitType::create(['name' => 'Petzl Croll Classic', 'brand' => 'Petzl', 'interval_months' => 6]);
    KitType::create(['name' => 'Petzl Volt Wind', 'brand' => 'Petzl', 'interval_months' => 6]);

    Http::fake(['api.x.ai/*' => Http::response(fakeXaiResponse([]), 200)]);

    app(KitTypeAiRefreshService::class)->run();

    $gapFillSeen = false;
    Http::recorded(function ($request) use (&$gapFillSeen) {
        $body = json_decode($request->body(), true);
        $userMessage = collect($body['messages'] ?? [])->firstWhere('role', 'user')['content'] ?? '';
        if (str_contains($userMessage, 'Petzl Croll Classic') && str_contains($userMessage, 'Petzl Volt Wind')) {
            $gapFillSeen = true;
        }
    });

    expect($gapFillSeen)->toBeTrue();
});
