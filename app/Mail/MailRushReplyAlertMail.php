<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Prospect;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailRushReplyAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ?Prospect $prospect,
        public readonly string $fromEmail,
        public readonly string $replySubject,
        public readonly string $replyText,
    ) {
        $this->mailer = 'smtp_alert';
    }

    public function envelope(): Envelope
    {
        $who = $this->prospect?->company_name ?: ($this->fromEmail !== '' ? $this->fromEmail : 'un contacto');

        return new Envelope(
            from: new Address(
                (string) config('mail.alert.from.address'),
                (string) config('mail.alert.from.name'),
            ),
            subject: 'Respuesta de MailRush: '.$who,
        );
    }

    public function content(): Content
    {
        $prospect = $this->prospect;
        $lines = [
            'Alguien respondió un correo enviado por MailRush.',
            '',
            'Empresa: '.($prospect?->company_name ?: '—'),
            'Correo: '.($this->fromEmail !== '' ? $this->fromEmail : ($prospect?->email ?: '—')),
            'Asunto: '.($this->replySubject !== '' ? $this->replySubject : '—'),
            '',
            $this->replyText !== '' ? $this->replyText : 'MailRush no incluyó el texto de la respuesta.',
        ];

        if ($prospect !== null) {
            $lines[] = '';
            $lines[] = 'Prospecto: '.url('/admin/prospects/'.$prospect->id);
        }

        $text = implode("\n", $lines);

        return new Content(
            htmlString: nl2br(e($text)),
            text: 'mail.prospect-text',
            with: [
                'textBody' => $text,
            ],
        );
    }
}
