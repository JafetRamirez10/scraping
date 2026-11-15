<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Prospecting\SendProspectEmailAction;
use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectStatus;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Models\ProspectSequence;
use App\Services\Mail\OutboundMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SendFailedRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_failed_is_retried_twice_one_hour_apart(): void
    {
        $this->mock(OutboundMailer::class, function ($mock): void {
            $mock->shouldReceive('send')->andThrow(new RuntimeException('smtp down'));
        });

        $this->freezeTime();
        $email = $this->pendingEmail();
        $action = app(SendProspectEmailAction::class);

        $action->execute($email);
        $email->refresh();

        $this->assertSame(ProspectEmailStatus::Pending, $email->status);
        $this->assertSame(1, $email->send_attempts);
        $this->assertSame('Send failed', $email->error_message);
        $this->assertSame(now()->format('Y-m-d H:i'), $email->last_attempt_at?->format('Y-m-d H:i'));
        $this->assertSame(now()->addHour()->format('Y-m-d H:i'), $email->scheduled_at->format('Y-m-d H:i'));

        $this->travel(1)->hour();
        $action->execute($email);
        $email->refresh();

        $this->assertSame(ProspectEmailStatus::Pending, $email->status);
        $this->assertSame(2, $email->send_attempts);
        $this->assertSame(now()->addHour()->format('Y-m-d H:i'), $email->scheduled_at->format('Y-m-d H:i'));

        $this->travel(1)->hour();
        $action->execute($email);
        $email->refresh();

        $this->assertSame(ProspectEmailStatus::Failed, $email->status);
        $this->assertSame(3, $email->send_attempts);
        $this->assertSame('Send failed', $email->error_message);
    }

    private function pendingEmail(): ProspectEmail
    {
        $category = Category::query()->create([
            'name' => 'Logística',
            'slug' => 'logistica-retry',
            'search_query' => 'logistica',
        ]);
        $template = EmailTemplate::query()->create([
            'name' => 'Paso 1',
            'step' => 1,
            'subject' => 'Hola',
            'body_html' => '<p>Hola</p>',
            'body_text' => 'Hola',
        ]);
        $prospect = Prospect::query()->create([
            'category_id' => $category->id,
            'company_name' => 'Empresa',
            'email' => 'lead@example.org',
            'status' => ProspectStatus::InSequence,
        ]);
        $sequence = ProspectSequence::query()->create([
            'prospect_id' => $prospect->id,
            'started_at' => now(),
        ]);

        return ProspectEmail::query()->create([
            'prospect_id' => $prospect->id,
            'prospect_sequence_id' => $sequence->id,
            'email_template_id' => $template->id,
            'step' => 1,
            'scheduled_at' => now()->subMinute(),
            'status' => ProspectEmailStatus::Pending,
        ]);
    }
}
