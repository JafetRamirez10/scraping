<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectStatus;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Models\ProspectSequence;
use App\Models\SuppressionListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailRushWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_signature_is_rejected(): void
    {
        config(['services.mailrush.webhook_secret' => 'whsec_test']);

        $this->postJson('/webhooks/mailrush', ['event_type' => 'test'], [
            'X-MailRush-Signature' => 'sha256=nope',
        ])->assertUnauthorized();
    }

    public function test_signed_bounce_suppresses_the_matched_prospect(): void
    {
        $secret = 'whsec_test';
        config(['services.mailrush.webhook_secret' => $secret]);

        $category = Category::query()->create([
            'name' => 'Logística',
            'slug' => 'logistica',
            'search_query' => 'logistica',
        ]);
        $template = EmailTemplate::query()->create([
            'name' => 'Paso 1',
            'step' => 1,
            'subject' => 'Hola',
            'body_html' => '<p>Hola</p>',
        ]);
        $prospect = Prospect::query()->create([
            'category_id' => $category->id,
            'email' => 'lead@example.org',
            'status' => ProspectStatus::InSequence,
        ]);
        $sequence = ProspectSequence::query()->create([
            'prospect_id' => $prospect->id,
            'started_at' => now(),
        ]);
        ProspectEmail::query()->create([
            'prospect_id' => $prospect->id,
            'prospect_sequence_id' => $sequence->id,
            'email_template_id' => $template->id,
            'step' => 1,
            'scheduled_at' => now()->subHour(),
            'sent_at' => now()->subHour(),
            'status' => ProspectEmailStatus::Sent,
            'provider_message_id' => '<message-id@your-smtp>',
        ]);

        $body = json_encode([
            'organization_id' => 'org_1',
            'event_type' => 'bounce',
            'event_id' => '<report-1a2b3c@mx.example.net>',
            'timestamp' => '2026-11-06T15:40:19.000Z',
            'data' => [
                'related_outgoing_message_id' => '<message-id@your-smtp>',
                'original_recipient' => 'lead@example.org',
                'bounce_category' => 'hard',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/webhooks/mailrush',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_MAILRUSH_SIGNATURE' => 'sha256='.hash_hmac('sha256', $body, $secret),
            ],
            $body,
        )->assertOk();

        $this->assertDatabaseHas('suppression_list', [
            'email' => 'lead@example.org',
        ]);
        $this->assertNotNull(SuppressionListEntry::query()->where('email', 'lead@example.org')->first());
        $this->assertNotNull($sequence->fresh()->cancelled_at);
    }
}
