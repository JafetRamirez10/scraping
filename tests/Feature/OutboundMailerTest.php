<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\MailRushRateLimited;
use App\Mail\TemplateTestMail;
use App\Services\Mail\MailRushClient;
use App\Services\Mail\OutboundMailer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OutboundMailerTest extends TestCase
{
    public function test_brevo_provider_keeps_using_smtp(): void
    {
        config(['mail.provider' => 'brevo']);
        Mail::fake();

        $messageId = app(OutboundMailer::class)->send(
            new TemplateTestMail('Asunto', '<p>Hola</p>', 'Hola'),
            'destino@empresa.com',
            'Empresa',
            'Asunto',
            '<p>Hola</p>',
            'Hola',
        );

        $this->assertNull($messageId);
        Mail::assertSent(TemplateTestMail::class, function (TemplateTestMail $mail): bool {
            return $mail->hasTo('destino@empresa.com') && $mail->subjectLine === 'Asunto';
        });
    }

    public function test_mailrush_provider_sends_through_the_api(): void
    {
        config([
            'mail.provider' => 'mailrush',
            'mail.from.address' => 'soporte@jramirezr.com',
            'services.mailrush.api_key' => 'test-key',
            'services.mailrush.base_url' => 'https://app2.mailrush.io/api/v2',
        ]);

        Http::fake([
            'https://app2.mailrush.io/api/v2/email' => Http::response([
                'success' => true,
                'messageId' => '<abc@mailrush>',
            ]),
        ]);

        $messageId = app(OutboundMailer::class)->send(
            new TemplateTestMail('Asunto', '<p>Hola</p>', 'Hola'),
            'destino@empresa.com',
            null,
            'Asunto',
            '<p>Hola</p>',
            'Hola',
            'prospect-email-1',
        );

        $this->assertSame('<abc@mailrush>', $messageId);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://app2.mailrush.io/api/v2/email'
                && $request->hasHeader('x-api-key', 'test-key')
                && $request->hasHeader('Idempotency-Key', 'prospect-email-1')
                && $request['fromEmail'] === 'soporte@jramirezr.com'
                && $request['recipientEmail'] === 'destino@empresa.com'
                && $request['subject'] === 'Asunto'
                && $request['html'] === '<p>Hola</p>';
        });
    }

    public function test_mailrush_rate_limit_exposes_retry_after(): void
    {
        config([
            'services.mailrush.api_key' => 'test-key',
            'mail.from.address' => 'soporte@jramirezr.com',
            'services.mailrush.base_url' => 'https://app2.mailrush.io/api/v2',
        ]);

        Http::fake([
            'https://app2.mailrush.io/api/v2/email' => Http::response(
                ['code' => 'SENDER_HOURLY_LIMIT'],
                429,
                ['Retry-After' => '120'],
            ),
        ]);

        try {
            app(MailRushClient::class)->send('destino@empresa.com', 'Asunto', '<p>Hola</p>', 'Hola');
            $this->fail('Expected MailRush rate limit.');
        } catch (MailRushRateLimited $exception) {
            $this->assertSame(120, $exception->retryAfterSeconds);
        }
    }
}
