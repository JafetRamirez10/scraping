<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Prospecting\SetProspectOutcomeAction;
use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectOutcome;
use App\Enums\ProspectStatus;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Models\ProspectSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetProspectOutcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_hired_outcome_stops_pending_emails(): void
    {
        $prospect = $this->prospectWithPendingEmail();

        app(SetProspectOutcomeAction::class)->execute($prospect, ProspectOutcome::Hired);

        $prospect->refresh();

        $this->assertSame(ProspectOutcome::Hired, $prospect->outcome);
        $this->assertFalse($prospect->isContactable());
        $this->assertSame(
            ProspectEmailStatus::Skipped,
            $prospect->emails()->first()->status,
        );
        $this->assertNotNull($prospect->sequence->fresh()->cancelled_at);
    }

    public function test_reply_outcome_keeps_pending_emails(): void
    {
        $prospect = $this->prospectWithPendingEmail();

        app(SetProspectOutcomeAction::class)->execute($prospect, ProspectOutcome::Replied);

        $prospect->refresh();

        $this->assertSame(ProspectOutcome::Replied, $prospect->outcome);
        $this->assertTrue($prospect->isContactable());
        $this->assertSame(
            ProspectEmailStatus::Pending,
            $prospect->emails()->first()->status,
        );
        $this->assertNull($prospect->sequence->fresh()->cancelled_at);
    }

    private function prospectWithPendingEmail(): Prospect
    {
        $category = Category::query()->create([
            'name' => 'Logística',
            'slug' => 'logistica-outcome',
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
            'scheduled_at' => now()->addDay(),
            'status' => ProspectEmailStatus::Pending,
        ]);

        return $prospect->fresh(['emails', 'sequence']);
    }
}
