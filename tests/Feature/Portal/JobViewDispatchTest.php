<?php

use App\Models\Client;
use App\Models\Job;
use App\Models\User;

function makeDispatchSetup(string $suffix = ''): array
{
    $client = Client::create([
        'name' => 'Dispatch Client '.$suffix,
        'address' => '1 Dispatch Street',
        'contact_email' => 'dispatch'.$suffix.'@test.com',
        'phone' => '01234567890',
    ]);

    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    return [$client, $user];
}

it('redirects client_viewers to the portal job page', function () {
    [$client, $user] = makeDispatchSetup('cv');

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->get(route('jobs.view', $job))
        ->assertRedirect(route('portal.jobs.show', $job));
});

it('redirects admin users to the back-office job page', function () {
    [$client, $user] = makeDispatchSetup('admin');
    $admin = User::factory()->create(['role' => 'admin']);

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->get(route('jobs.view', $job))
        ->assertRedirect(route('jobs.show', $job));
});

it('redirects inspector users to the back-office job page', function () {
    [$client, $user] = makeDispatchSetup('insp');
    $inspector = User::factory()->create(['role' => 'inspector']);

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->actingAs($inspector)
        ->get(route('jobs.view', $job))
        ->assertRedirect(route('jobs.show', $job));
});

it('forbids client_viewers from another client', function () {
    [, $userA] = makeDispatchSetup('xa');
    [$clientB, $userB] = makeDispatchSetup('xb');

    $job = Job::create([
        'client_id' => $clientB->id,
        'created_by_user_id' => $userB->id,
        'status' => 'draft',
    ]);

    $this->actingAs($userA)
        ->get(route('jobs.view', $job))
        ->assertForbidden();
});

it('redirects guests to login', function () {
    [$client, $user] = makeDispatchSetup('guest');

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->get(route('jobs.view', $job))
        ->assertRedirect(route('login'));
});
