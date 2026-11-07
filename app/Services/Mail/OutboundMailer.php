<?php

declare(strict_types=1);

namespace App\Services\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class OutboundMailer
{
    public function __construct(
        private readonly MailRushClient $mailRush,
    ) {}

    public function usesMailRush(): bool
    {
        return strtolower((string) config('mail.provider', 'brevo')) === 'mailrush';
    }

    public function send(
        Mailable $mailable,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $html,
        string $text,
        ?string $idempotencyKey = null,
    ): ?string {
        if ($this->usesMailRush()) {
            return $this->mailRush->send($toEmail, $subject, $html, $text, $idempotencyKey);
        }

        Mail::to($toEmail, $toName)->send($mailable);

        return null;
    }
}
