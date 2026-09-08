<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ProspectEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProspectOutreachMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ProspectEmail $prospectEmail,
        public readonly string $subjectLine,
        public readonly string $htmlBody,
        public readonly string $textBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
            text: 'mail.prospect-text',
            with: [
                'textBody' => $this->textBody,
            ],
        );
    }
}
