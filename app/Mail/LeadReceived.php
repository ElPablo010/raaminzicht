<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead)
    {
    }

    public function envelope(): Envelope
    {
        $label = $this->lead->type === 'contact' ? 'Nieuw contactbericht' : 'Nieuwe offerteaanvraag';

        return new Envelope(
            subject: $label.' van '.$this->lead->name,
            replyTo: [$this->lead->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.lead-received',
            with: ['lead' => $this->lead],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
