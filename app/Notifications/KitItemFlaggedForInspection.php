<?php

namespace App\Notifications;

use App\Models\KitItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KitItemFlaggedForInspection extends Notification
{
    use Queueable;

    public function __construct(
        public KitItem $kitItem,
        public string $flagNotes = ''
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $item = $this->kitItem;
        $message = (new MailMessage)
            ->subject("Client Flagged Item for Inspection — {$item->kitType->name} ({$item->asset_tag})")
            ->greeting('Inspection Required')
            ->line("**Client:** {$item->client->name}")
            ->line("**Equipment:** {$item->kitType->name}")
            ->line('**Asset Tag:** '.($item->asset_tag ?? '—'))
            ->line('**Serial No.:** '.($item->serial_no ?? '—'));

        if ($this->flagNotes) {
            $message->line("**Notes from client:** {$this->flagNotes}");
        }

        return $message
            ->action('View Item', route('clients.kit-items.show', [$item->client, $item]))
            ->line('This item has been added to your inspection todo list on the dashboard.');
    }
}
