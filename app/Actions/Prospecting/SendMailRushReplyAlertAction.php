<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Mail\MailRushReplyAlertMail;
use App\Models\Prospect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMailRushReplyAlertAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(?Prospect $prospect, string $fromEmail, array $data): void
    {
        $to = (string) config('mail.alert.to');
        $fromAddress = (string) config('mail.alert.from.address');
        $host = (string) config('mail.mailers.smtp_alert.host');

        if ($to === '' || $fromAddress === '' || $host === '') {
            Log::warning('MailRush reply alert skipped because SMTP alert is not configured');

            return;
        }

        try {
            Mail::mailer('smtp_alert')->to($to)->send(new MailRushReplyAlertMail(
                prospect: $prospect,
                fromEmail: $fromEmail,
                replySubject: trim((string) ($data['subject'] ?? '')),
                replyText: trim((string) ($data['text'] ?? $data['snippet'] ?? '')),
            ));
        } catch (Throwable $exception) {
            Log::error('MailRush reply alert failed', [
                'prospect_id' => $prospect?->id,
            ]);
        }
    }
}
