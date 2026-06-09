<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
        $label = match ($this->lead->type) {
            'contact' => 'Nieuw contactbericht',
            'afspraak' => 'Nieuwe afspraakaanvraag',
            default => 'Nieuwe offerteaanvraag',
        };

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
        return collect($this->lead->attachments ?? [])
            ->map(fn (array $file) => Attachment::fromStorageDisk('local', $file['path'])
                ->as($file['name'] ?? basename($file['path'])))
            ->all();
    }
}
