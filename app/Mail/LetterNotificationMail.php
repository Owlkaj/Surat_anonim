<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LetterNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $secretUrl;
    public string $targetLabel;

    /**
     * Create a new message instance.
     */
    public function __construct(string $secretUrl, string $targetLabel = 'Penerima')
    {
        $this->secretUrl = $secretUrl;
        $this->targetLabel = $targetLabel;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kamu menerima sebuah surat anonim di SuratAnonim',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.letter_notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
