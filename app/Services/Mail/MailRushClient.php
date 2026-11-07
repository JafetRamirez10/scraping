<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Exceptions\MailRushRateLimited;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MailRushClient
{
    public function send(
        string $toEmail,
        string $subject,
        string $html,
        string $text,
        ?string $idempotencyKey = null,
    ): string {
        $apiKey = (string) config('services.mailrush.api_key');
        $fromEmail = (string) config('mail.from.address');

        if ($apiKey === '') {
            throw new RuntimeException('MailRush API key is not configured.');
        }

        if ($fromEmail === '') {
            throw new RuntimeException('MAIL_FROM_ADDRESS is not configured.');
        }

        $pending = Http::withHeaders([
            'x-api-key' => $apiKey,
        ])
            ->acceptJson()
            ->timeout((int) config('services.mailrush.timeout', 30));

        if (filled($idempotencyKey)) {
            $pending = $pending->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ]);
        }

        $baseUrl = rtrim((string) config('services.mailrush.base_url', 'https://app2.mailrush.io/api/v2'), '/');

        $response = $pending->post("{$baseUrl}/email", [
            'fromEmail' => $fromEmail,
            'recipientEmail' => $toEmail,
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ]);

        if ($response->status() === 429) {
            $retryAfter = (int) $response->header('Retry-After');

            throw new MailRushRateLimited($retryAfter > 0 ? $retryAfter : 60);
        }

        if (! $response->successful()) {
            throw new RuntimeException('MailRush API request failed with status '.$response->status());
        }

        $messageId = (string) data_get($response->json(), 'messageId', '');

        if ($messageId === '') {
            throw new RuntimeException('MailRush API returned no message id.');
        }

        return $messageId;
    }
}
