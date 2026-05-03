<?php

namespace App\Notifications;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Job $job,
        public ?string $from,
        public string $to,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->job;
        $statusLabel = self::label($this->to);
        $itemCount = $job->kitItems()->count();

        $message = (new MailMessage)
            ->subject("LOLER inspection job {$job->job_number}: now {$statusLabel}")
            ->greeting('Job Update')
            ->line("Your inspection job **{$job->job_number}** is now: **{$statusLabel}**.")
            ->line("Items on this job: {$itemCount}");

        if ($this->from) {
            $fromLabel = self::label($this->from);
            $message->line("Previous status: {$fromLabel}");
        }

        return $message
            ->action('View Job', route('portal.jobs.show', $job))
            ->line('Thank you for using our LOLER inspection service.');
    }

    private static function label(string $status): string
    {
        return match ($status) {
            'draft' => 'Awaiting Review',
            'open' => 'Items Received',
            'in_progress' => 'In Inspection',
            'complete' => 'Ready for Collection',
            'returned' => 'Returned',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
