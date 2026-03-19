<?php

namespace App\Mail;

use App\Models\KitItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InspectionDueSoon extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public KitItem $kitItem) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'LOLER Inspection Due Soon — ' . $this->kitItem->kitType->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.inspection-due-soon',
        );
    }
}
