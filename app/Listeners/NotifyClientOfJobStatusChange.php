<?php

namespace App\Listeners;

use App\Events\JobStatusChanged;
use App\Models\User;
use App\Notifications\JobStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyClientOfJobStatusChange implements ShouldQueue
{
    public function handle(JobStatusChanged $event): void
    {
        $clientViewers = User::query()
            ->where('client_id', $event->job->client_id)
            ->where('role', 'client_viewer')
            ->get();

        if ($clientViewers->isEmpty()) {
            return;
        }

        Notification::send(
            $clientViewers,
            new JobStatusChangedNotification($event->job, $event->from, $event->to)
        );
    }
}
