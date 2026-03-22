<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KitTypeRefreshComplete extends Notification
{
    use Queueable;

    /** @param array<string, mixed> $totals */
    public function __construct(public array $totals) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Equipment List Refresh Complete')
            ->line('The AI equipment refresh has finished.')
            ->line("{$this->totals['added']} new equipment types added.")
            ->line("{$this->totals['skipped']} types already existed (unchanged).");

        if (! empty($this->totals['errors'])) {
            $mail->line('Errors: '.implode('; ', $this->totals['errors']));
        }

        return $mail->action('View Kit Types', route('kit-types.index'));
    }
}
