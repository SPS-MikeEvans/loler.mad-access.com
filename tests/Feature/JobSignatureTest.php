<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Job;
use App\Models\User;

$fakeSignature = 'data:image/png;base64,'.base64_encode(str_repeat('x', 100));

// ─── Drop-off signature ───────────────────────────────────────────────────────

it('shows the drop-off signature page for a draft job', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->get(route('jobs.sign-drop-off', $job))
        ->assertOk();
});

it('redirects away from drop-off page when job is already open', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->open()->create();

    $this->actingAs($admin)
        ->get(route('jobs.sign-drop-off', $job))
        ->assertRedirect(route('jobs.show', $job));
});

it('captures drop-off signature and advances status to open', function () use (&$fakeSignature) {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->post(route('jobs.capture-drop-off', $job), [
            'digital_signature' => $fakeSignature,
            'signed_by' => 'Jane Doe',
        ])
        ->assertRedirect(route('jobs.show', $job));

    $job->refresh();
    expect($job->status)->toBe('open');
    expect($job->drop_off_signed_by)->toBe('Jane Doe');
    expect($job->drop_off_at)->not->toBeNull();
    expect($job->drop_off_signature_path)->not->toBeNull();

    expect(AuditLog::where('subject_type', 'Job')
        ->where('subject_id', $job->id)
        ->where('action', 'updated')
        ->exists()
    )->toBeTrue();
});

it('rejects drop-off signature without a name', function () use (&$fakeSignature) {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->post(route('jobs.capture-drop-off', $job), [
            'digital_signature' => $fakeSignature,
            'signed_by' => '',
        ])
        ->assertSessionHasErrors('signed_by');
});

it('rejects drop-off signature without a signature', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->post(route('jobs.capture-drop-off', $job), [
            'digital_signature' => '',
            'signed_by' => 'Jane Doe',
        ])
        ->assertSessionHasErrors('digital_signature');
});

it('rejects double drop-off on an already-open job', function () use (&$fakeSignature) {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->open()->create();

    $this->actingAs($admin)
        ->post(route('jobs.capture-drop-off', $job), [
            'digital_signature' => $fakeSignature,
            'signed_by' => 'Attacker',
        ])
        ->assertRedirect(route('jobs.show', $job));

    $job->refresh();
    expect($job->drop_off_signed_by)->not->toBe('Attacker');
});

// ─── Return signature ─────────────────────────────────────────────────────────

it('shows the return signature page for a complete job', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->complete()->create();

    $this->actingAs($admin)
        ->get(route('jobs.sign-return', $job))
        ->assertOk();
});

it('rejects return signature page when job is not complete', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->open()->create();

    $this->actingAs($admin)
        ->get(route('jobs.sign-return', $job))
        ->assertRedirect(route('jobs.show', $job));
});

it('captures return signature and advances status to returned', function () use (&$fakeSignature) {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->complete()->create();

    $this->actingAs($admin)
        ->post(route('jobs.capture-return', $job), [
            'digital_signature' => $fakeSignature,
            'signed_by' => 'Bob Smith',
        ])
        ->assertRedirect(route('jobs.show', $job));

    $job->refresh();
    expect($job->status)->toBe('returned');
    expect($job->return_signed_by)->toBe('Bob Smith');
    expect($job->return_at)->not->toBeNull();

    expect(AuditLog::where('subject_type', 'Job')
        ->where('subject_id', $job->id)
        ->where('action', 'updated')
        ->exists()
    )->toBeTrue();
});

it('rejects return signature when job is not complete', function () use (&$fakeSignature) {
    $admin = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->open()->create();

    $this->actingAs($admin)
        ->post(route('jobs.capture-return', $job), [
            'digital_signature' => $fakeSignature,
            'signed_by' => 'Bob Smith',
        ])
        ->assertRedirect(route('jobs.show', $job));

    expect($job->fresh()->status)->toBe('open');
});

it('forbids a client_viewer from capturing signatures', function () use (&$fakeSignature) {
    $client = Client::factory()->create();
    $viewer = User::factory()->clientViewer()->create(['client_id' => $client->id]);
    $job = Job::factory()->create(['status' => 'draft', 'client_id' => $client->id]);

    $this->actingAs($viewer)
        ->post(route('jobs.capture-drop-off', $job), [
            'digital_signature' => $fakeSignature,
            'signed_by' => 'Viewer',
        ])
        ->assertForbidden();
});
