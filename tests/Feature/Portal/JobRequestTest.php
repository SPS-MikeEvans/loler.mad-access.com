<?php

use App\Events\JobStatusChanged;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Job;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function makeJobReqSetup(string $suffix = ''): array
{
    $client = Client::create([
        'name' => 'Job Client '.$suffix,
        'address' => '1 Job Street',
        'contact_email' => 'job'.$suffix.'@test.com',
        'phone' => '01234567890',
    ]);

    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    $kitType = KitType::create([
        'name' => 'Job Rope '.$suffix,
        'interval_months' => 6,
        'inspection_price' => 25.00,
    ]);

    return [$client, $user, $kitType];
}

it('shows the create page with the auth clients items', function () {
    [$client, $user, $kitType] = makeJobReqSetup('list');

    $mine = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'JR-1', 'status' => 'in_service']);
    [$otherClient] = makeJobReqSetup('listb');
    $other = KitItem::create(['client_id' => $otherClient->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'OTHER-99', 'status' => 'in_service']);

    $this->actingAs($user)
        ->get(route('portal.jobs.create'))
        ->assertOk()
        ->assertSee('JR-1')
        ->assertDontSee('OTHER-99');
});

it('walks the wizard and creates a draft job', function () {
    Event::fake([JobStatusChanged::class]);

    [$client, $user, $kitType] = makeJobReqSetup('walk');

    $a = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'JR-A', 'status' => 'in_service']);
    $b = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'JR-B', 'status' => 'in_service']);

    $dropOff = now()->addDays(5)->toDateString();

    $this->actingAs($user)
        ->post(route('portal.jobs.date'), ['kit_item_ids' => [$a->id, $b->id]])
        ->assertOk();

    $this->actingAs($user)
        ->post(route('portal.jobs.review'), ['drop_off_at' => $dropOff, 'notes' => 'Please rush'])
        ->assertOk()
        ->assertSee('£50.00'); // 2 * £25

    $this->actingAs($user)
        ->post(route('portal.jobs.store'), [
            'kit_item_ids' => [$a->id, $b->id],
            'drop_off_at' => $dropOff,
            'notes' => 'Please rush',
        ])
        ->assertRedirect();

    $job = Job::where('client_id', $client->id)->latest()->first();
    expect($job)->not->toBeNull();
    expect($job->status)->toBe('draft');
    expect($job->created_by_user_id)->toBe($user->id);
    expect($job->job_number)->toMatch('/^JOB-\d{4}-\d{4}$/');
    expect($job->kitItems->pluck('id')->all())->toContain($a->id, $b->id);

    $audit = AuditLog::where('subject_type', 'Job')->where('subject_id', $job->id)->where('action', 'created')->first();
    expect($audit)->not->toBeNull();
    expect($audit->metadata['estimated_cost_pence'])->toBe(5000);

    Event::assertDispatched(JobStatusChanged::class);
});

it('rejects items belonging to another client', function () {
    [, $userA, $kitType] = makeJobReqSetup('reja');
    [$clientB] = makeJobReqSetup('rejb');

    $other = KitItem::create(['client_id' => $clientB->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'NOT-MINE', 'status' => 'in_service']);

    $this->actingAs($userA)
        ->post(route('portal.jobs.store'), [
            'kit_item_ids' => [$other->id],
            'drop_off_at' => now()->addDays(2)->toDateString(),
        ])
        ->assertSessionHasErrors('kit_item_ids.0');

    expect(Job::where('client_id', $clientB->id)->exists())->toBeFalse();
});

it('rejects retired items', function () {
    [$client, $user, $kitType] = makeJobReqSetup('ret');

    $retired = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'RET-1',
        'status' => 'retired',
    ]);

    $this->actingAs($user)
        ->post(route('portal.jobs.store'), [
            'kit_item_ids' => [$retired->id],
            'drop_off_at' => now()->addDays(2)->toDateString(),
        ])
        ->assertSessionHasErrors('kit_item_ids.0');
});

it('rejects past drop_off_at dates', function () {
    [$client, $user, $kitType] = makeJobReqSetup('past');

    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'P-1', 'status' => 'in_service']);

    $this->actingAs($user)
        ->post(route('portal.jobs.store'), [
            'kit_item_ids' => [$item->id],
            'drop_off_at' => now()->subDay()->toDateString(),
        ])
        ->assertSessionHasErrors('drop_off_at');
});

it('rejects drop-off dates more than 4 weeks away', function () {
    [$client, $user, $kitType] = makeJobReqSetup('far');

    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'F-1', 'status' => 'in_service']);

    $this->actingAs($user)
        ->post(route('portal.jobs.store'), [
            'kit_item_ids' => [$item->id],
            'drop_off_at' => now()->addWeeks(6)->toDateString(),
        ])
        ->assertSessionHasErrors('drop_off_at');
});

it('clears the wizard session after submit', function () {
    [$client, $user, $kitType] = makeJobReqSetup('clr');

    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'C-1', 'status' => 'in_service']);

    $this->actingAs($user)
        ->withSession(['portal.job_wizard' => ['kit_item_ids' => [$item->id], 'drop_off_at' => now()->addDays(2)->toDateString()]])
        ->post(route('portal.jobs.store'), [
            'kit_item_ids' => [$item->id],
            'drop_off_at' => now()->addDays(2)->toDateString(),
        ])
        ->assertRedirect()
        ->assertSessionMissing('portal.job_wizard');
});

it('makes the new draft visible to admin on the jobs index', function () {
    [$client, $user, $kitType] = makeJobReqSetup('admin');

    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'A-1', 'status' => 'in_service']);

    $this->actingAs($user)
        ->post(route('portal.jobs.store'), [
            'kit_item_ids' => [$item->id],
            'drop_off_at' => now()->addDays(2)->toDateString(),
        ])
        ->assertRedirect();

    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::latest()->first();

    $this->actingAs($admin)
        ->get(route('jobs.index'))
        ->assertOk()
        ->assertSee($job->job_number);
});
