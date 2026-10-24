<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Mail\Mailable;
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
        $replyToAddress = (string) config('mail.reply_to.address');
        $replyToName = (string) config('mail.reply_to.name');

        return new Envelope(
            subject: 'Ada surat baru untukmu — ' . config('app.name', 'SuratAnonim'),
            replyTo: $replyToAddress !== ''
                ? [new Address($replyToAddress, $replyToName !== '' ? $replyToName : null)]
                : [],
        );
    }

    /**
     * Header tambahan yang membantu penyedia email memahami jenis pesan ini.
     */
    public function headers(): Headers
    {
        $headers = [];

        $unsubscribeUrl = (string) config('mail.unsubscribe_url');

        if ($unsubscribeUrl !== '') {
            $headers['List-Unsubscribe'] = '<' . $unsubscribeUrl . '>';
        }

        return new Headers(text: $headers);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.letter_notification',
            text: 'emails.letter_notification_text',
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
