<?php

use App\Events\JobStatusChanged;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Inspection;
use App\Models\Job;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function makeMarkDoneSetup(string $suffix = ''): array
{
    $client = Client::create([
        'name' => 'MarkDone Client '.$suffix,
        'address' => '1 MarkDone St',
        'contact_email' => 'markdone'.$suffix.'@test.com',
        'phone' => '01234567890',
    ]);
    $admin = User::factory()->create(['role' => 'admin']);
    $kitType = KitType::create(['name' => 'MarkDone Rope '.$suffix, 'interval_months' => 6]);

    return [$client, $admin, $kitType];
}

function makeOpenJobWithItem(Client $client, KitItem $item): Job
{
    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => User::factory()->create(['role' => 'admin'])->id,
        'status' => 'open',
    ]);
    $job->kitItems()->sync([$item->id => ['condition_notes' => null]]);

    return $job;
}

function makeUnlinkedInspection(KitItem $item, User $inspector, ?string $date = null): Inspection
{
    return Inspection::create([
        'kit_item_id' => $item->id,
        'inspector_user_id' => $inspector->id,
        'status' => 'complete',
        'inspection_date' => $date ?? now()->subDays(2)->toDateString(),
        'next_due_date' => now()->addMonths(6)->toDateString(),
        'overall_status' => 'pass',
    ]);
}

it('links the latest unlinked complete inspection to the job', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('latest');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-1', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $item);

    $inspection = makeUnlinkedInspection($item, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect(route('jobs.show', $job));

    expect($inspection->refresh()->inspection_job_id)->toBe($job->id);
});

it('lets admin pick a specific inspection when multiple unlinked exist', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('pick');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-2', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $item);

    $older = makeUnlinkedInspection($item, $admin, now()->subMonths(3)->toDateString());
    $newer = makeUnlinkedInspection($item, $admin, now()->subDays(2)->toDateString());

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]), ['inspection_id' => $older->id])
        ->assertRedirect();

    expect($older->refresh()->inspection_job_id)->toBe($job->id);
    expect($newer->refresh()->inspection_job_id)->toBeNull();
});

it('errors when no unlinked complete inspection exists', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('none');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-3', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $item);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Inspection::where('inspection_job_id', $job->id)->exists())->toBeFalse();
});

it('errors when item already linked to an inspection on this job', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('already');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-4', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $item);

    Inspection::create([
        'kit_item_id' => $item->id,
        'inspector_user_id' => $admin->id,
        'inspection_job_id' => $job->id,
        'status' => 'complete',
        'inspection_date' => now()->toDateString(),
        'next_due_date' => now()->addMonths(6)->toDateString(),
        'overall_status' => 'pass',
    ]);
    $unlinked = makeUnlinkedInspection($item, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($unlinked->refresh()->inspection_job_id)->toBeNull();
});

it('rejects mark-done on draft jobs', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('draft');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-5', 'status' => 'in_service']);
    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $admin->id,
        'status' => 'draft',
    ]);
    $job->kitItems()->sync([$item->id => ['condition_notes' => null]]);
    $unlinked = makeUnlinkedInspection($item, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($unlinked->refresh()->inspection_job_id)->toBeNull();
});

it('rejects mark-done on complete jobs', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('cmpl');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-6', 'status' => 'in_service']);
    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $admin->id,
        'status' => 'complete',
    ]);
    $job->kitItems()->sync([$item->id => ['condition_notes' => null]]);
    $unlinked = makeUnlinkedInspection($item, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($unlinked->refresh()->inspection_job_id)->toBeNull();
});

it('rejects an item that does not belong to the job', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('not-in-job');
    $itemA = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-A', 'status' => 'in_service']);
    $itemB = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-B', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $itemA);
    $unlinked = makeUnlinkedInspection($itemB, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $itemB]))
        ->assertNotFound();
});

it('forbids cross-client items', function () {
    [$clientA, $admin, $kitTypeA] = makeMarkDoneSetup('xa');
    [$clientB, , $kitTypeB] = makeMarkDoneSetup('xb');
    $itemA = KitItem::create(['client_id' => $clientA->id, 'kit_type_id' => $kitTypeA->id, 'asset_tag' => 'XA-1', 'status' => 'in_service']);
    $itemB = KitItem::create(['client_id' => $clientB->id, 'kit_type_id' => $kitTypeB->id, 'asset_tag' => 'XB-1', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($clientA, $itemA);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $itemB]))
        ->assertForbidden();
});

it('auto-transitions an open job to in_progress on first link', function () {
    Event::fake([JobStatusChanged::class]);

    [$client, $admin, $kitType] = makeMarkDoneSetup('promote');
    $itemA = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-P-A', 'status' => 'in_service']);
    $itemB = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-P-B', 'status' => 'in_service']);

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $admin->id,
        'status' => 'open',
    ]);
    $job->kitItems()->sync([
        $itemA->id => ['condition_notes' => null],
        $itemB->id => ['condition_notes' => null],
    ]);

    makeUnlinkedInspection($itemA, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $itemA]))
        ->assertRedirect();

    // Only one of two items is done → job sits at in_progress
    expect($job->fresh()->status)->toBe('in_progress');
    Event::assertDispatched(JobStatusChanged::class);
});

it('auto-completes the job when the last item is marked done', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('autocomplete');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-AC', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $item);
    makeUnlinkedInspection($item, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect();

    // open → in_progress → complete (single item, single inspection link triggers full flow)
    expect($job->fresh()->status)->toBe('complete');
});

it('writes an audit log row with job + kit item metadata', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('audit');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-AU', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $item);
    $insp = makeUnlinkedInspection($item, $admin);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect();

    $log = AuditLog::where('subject_type', 'Inspection')
        ->where('subject_id', $insp->id)
        ->where('action', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->metadata['job_id'])->toBe($job->id);
    expect($log->metadata['kit_item_id'])->toBe($item->id);
});

it('only counts complete inspections (skips drafts)', function () {
    [$client, $admin, $kitType] = makeMarkDoneSetup('draft-insp');
    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'MD-D', 'status' => 'in_service']);
    $job = makeOpenJobWithItem($client, $item);

    Inspection::create([
        'kit_item_id' => $item->id,
        'inspector_user_id' => $admin->id,
        'status' => 'draft',
        'inspection_date' => now()->subDays(1)->toDateString(),
        'next_due_date' => now()->addMonths(6)->toDateString(),
        'overall_status' => 'pass',
    ]);

    $this->actingAs($admin)
        ->post(route('jobs.kit-items.mark-done', [$job, $item]))
        ->assertRedirect()
        ->assertSessionHas('error');
});
