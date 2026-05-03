<?php

use App\Events\JobStatusChanged;
use App\Listeners\NotifyClientOfJobStatusChange;
use App\Models\Client;
use App\Models\Job;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use App\Notifications\JobStatusChangedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

function makeNotifSetup(string $suffix = ''): array
{
    $client = Client::create([
        'name' => 'Notif Client '.$suffix,
        'address' => '1 Notif Street',
        'contact_email' => 'notif'.$suffix.'@test.com',
        'phone' => '01234567890',
    ]);

    $user = User::factory()->clientViewer()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
    ]);

    $kitType = KitType::create(['name' => 'Notif Rope '.$suffix, 'interval_months' => 6]);

    return [$client, $user, $kitType];
}

it('notifies client_viewer users of the auth client when a job status changes', function () {
    Notification::fake();

    [$client, $user] = makeNotifSetup('a');

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'draft',
    ]);

    // Synchronously trigger the listener
    (new NotifyClientOfJobStatusChange)->handle(new JobStatusChanged($job, null, 'draft'));

    Notification::assertSentTo($user, JobStatusChangedNotification::class);
});

it('does not notify admin or inspector users of the same client', function () {
    Notification::fake();

    [$client, $user] = makeNotifSetup('b');
    $admin = User::factory()->create(['role' => 'admin', 'client_id' => $client->id]);
    $inspector = User::factory()->create(['role' => 'inspector', 'client_id' => $client->id]);

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'draft',
    ]);

    (new NotifyClientOfJobStatusChange)->handle(new JobStatusChanged($job, 'draft', 'open'));

    Notification::assertSentTo($user, JobStatusChangedNotification::class);
    Notification::assertNotSentTo($admin, JobStatusChangedNotification::class);
    Notification::assertNotSentTo($inspector, JobStatusChangedNotification::class);
});

it('builds a mail with the job number and status label', function () {
    [$client, $user, $kitType] = makeNotifSetup('mail');

    $item = KitItem::create(['client_id' => $client->id, 'kit_type_id' => $kitType->id, 'asset_tag' => 'M-1', 'status' => 'in_service']);
    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'open',
    ]);
    $job->kitItems()->sync([$item->id => ['condition_notes' => null]]);

    $mail = (new JobStatusChangedNotification($job, 'draft', 'open'))->toMail($user);
    $rendered = (string) $mail->render();

    expect($rendered)->toContain($job->job_number);
    expect($rendered)->toContain('Items Received');
});

it('dispatches JobStatusChanged when the job status changes', function () {
    Event::fake([JobStatusChanged::class]);

    [$client, $user] = makeNotifSetup('dispatch');

    $job = Job::create([
        'client_id' => $client->id,
        'created_by_user_id' => $user->id,
        'status' => 'draft',
    ]);

    $job->update(['status' => 'open']);

    Event::assertDispatched(JobStatusChanged::class, function (JobStatusChanged $e) use ($job) {
        return $e->job->id === $job->id && $e->from === 'draft' && $e->to === 'open';
    });
});
