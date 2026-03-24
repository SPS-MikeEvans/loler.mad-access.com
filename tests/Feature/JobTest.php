<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Inspection;
use App\Models\Job;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;

// ─── Authorization ───────────────────────────────────────────────────────────

it('redirects guests away from jobs index', function () {
    $this->get(route('jobs.index'))->assertRedirect(route('login'));
});

it('allows admins to view jobs index', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get(route('jobs.index'))->assertOk();
});

it('allows inspectors to view jobs index', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $this->actingAs($inspector)->get(route('jobs.index'))->assertOk();
});

it('forbids client_viewers from viewing admin jobs index', function () {
    $client = Client::factory()->create();
    $viewer = User::factory()->clientViewer()->create(['client_id' => $client->id]);
    $this->actingAs($viewer)->get(route('jobs.index'))->assertForbidden();
});

// ─── Job Number Generation ────────────────────────────────────────────────────

it('auto-generates a unique job number on creation', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $admin->id,
    ]);

    expect($job->job_number)->toMatch('/^JOB-\d{4}-\d{4}$/');
});

it('generates sequential job numbers', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();

    $job1 = Job::create(['client_id' => $client->id, 'created_by_user_id' => $admin->id]);
    $job2 = Job::create(['client_id' => $client->id, 'created_by_user_id' => $admin->id]);

    $num1 = (int) substr($job1->job_number, -4);
    $num2 = (int) substr($job2->job_number, -4);

    expect($num2)->toBe($num1 + 1);
});

it('auto-generates a bag_qr_code uuid on creation', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();

    $job = Job::create(['client_id' => $client->id, 'created_by_user_id' => $admin->id]);

    expect($job->bag_qr_code)->toBeString()->not->toBeEmpty();
});

// ─── Status Transitions ───────────────────────────────────────────────────────

it('can transition from draft to open', function () {
    $job = Job::factory()->create(['status' => 'draft']);
    expect($job->canTransitionTo('open'))->toBeTrue();
});

it('cannot transition from draft to complete', function () {
    $job = Job::factory()->create(['status' => 'draft']);
    expect($job->canTransitionTo('complete'))->toBeFalse();
});

it('cannot transition returned back to open', function () {
    $job = Job::factory()->returned()->create();
    expect($job->canTransitionTo('open'))->toBeFalse();
});

// ─── CRUD ─────────────────────────────────────────────────────────────────────

it('allows an admin to create a job', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();

    $this->actingAs($admin)
        ->post(route('jobs.store'), [
            'client_id' => $client->id,
            'notes' => 'Test job',
        ])
        ->assertRedirect();

    expect(Job::where('client_id', $client->id)->exists())->toBeTrue();
});

it('shows a job to an admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create();

    $this->actingAs($admin)
        ->get(route('jobs.show', $job))
        ->assertOk()
        ->assertSee($job->job_number);
});

it('allows editing a draft job', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->get(route('jobs.edit', $job))
        ->assertOk();
});

it('redirects away from editing a returned job', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->returned()->create();

    $this->actingAs($admin)
        ->get(route('jobs.edit', $job))
        ->assertRedirect(route('jobs.show', $job));
});

// ─── Deletion ─────────────────────────────────────────────────────────────────

it('forbids an inspector from deleting a job', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);
    $job = Job::factory()->returned()->create();

    $this->actingAs($inspector)
        ->delete(route('jobs.destroy', $job))
        ->assertForbidden();
});

it('requires password confirmation before admin can delete a job', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->returned()->create();

    $this->actingAs($admin)->get(route('jobs.show', $job));

    $this->actingAs($admin)
        ->delete(route('jobs.destroy', $job), ['confirmation_phrase' => "DELETE-JOB-{$job->id}"])
        ->assertRedirect(route('password.confirm'));
});

it('rejects deletion of a non-returned job', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->open()->create();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('jobs.show', $job));

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('jobs.destroy', $job), ['confirmation_phrase' => "DELETE-JOB-{$job->id}"])
        ->assertRedirect(route('jobs.show', $job));

    expect(Job::find($job->id))->not->toBeNull();
});

it('deletes a returned job with correct phrase and password confirmed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->returned()->create();

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('jobs.show', $job));

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('jobs.destroy', $job), ['confirmation_phrase' => "DELETE-JOB-{$job->id}"])
        ->assertRedirect(route('jobs.index'));

    expect(Job::withTrashed()->find($job->id)?->deleted_at)->not->toBeNull();
    expect(AuditLog::where('subject_type', 'Job')->where('subject_id', $job->id)->where('action', 'deleted')->exists())->toBeTrue();
});

// ─── Bag QR scan ─────────────────────────────────────────────────────────────

it('resolves a bag QR scan to the job for an admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create();

    $this->actingAs($admin)
        ->get(route('jobs.scan-bag', $job->bag_qr_code))
        ->assertRedirect(route('jobs.show', $job));
});

it('forbids a client viewer from scanning another clients bag', function () {
    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();
    $viewer = User::factory()->clientViewer()->create(['client_id' => $clientA->id]);
    $job = Job::factory()->create(['client_id' => $clientB->id]);

    $this->actingAs($viewer)
        ->get(route('jobs.scan-bag', $job->bag_qr_code))
        ->assertForbidden();
});

// ─── Inspection progress auto-advance ────────────────────────────────────────

it('auto-advances job to in_progress when first inspection is created', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = Client::factory()->create();
    $kitType = KitType::create(['name' => 'Auto Adv Type', 'interval_months' => 6]);
    $item = KitItem::factory()->create(['client_id' => $client->id, 'kit_type_id' => $kitType->id]);
    $job = Job::factory()->open()->create(['client_id' => $client->id]);
    $job->kitItems()->attach($item->id);

    $inspection = Inspection::create([
        'kit_item_id' => $item->id,
        'inspector_user_id' => $admin->id,
        'inspection_job_id' => $job->id,
        'inspection_date' => now()->toDateString(),
        'next_due_date' => now()->addMonths(6)->toDateString(),
        'overall_status' => 'pass',
        'status' => 'draft',
    ]);

    expect($job->fresh()->status)->toBe('in_progress');
});
