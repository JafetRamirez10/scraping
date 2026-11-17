<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Prospecting\ScheduleNextEmailAction;
use App\Actions\Prospecting\SetProspectEmailStepAction;
use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectStatus;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Models\ProspectSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetProspectEmailStepTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_step_two_also_disables_step_three(): void
    {
        $prospect = $this->prospectWithSteps();

        app(SetProspectEmailStepAction::class)->sync($prospect, false, true, false);

        $prospect->refresh();

        $this->assertTrue($prospect->disable_step_2);
        $this->assertTrue($prospect->disable_step_3);
        $this->assertTrue($prospect->isStepDisabled(3));
        $this->assertSame(ProspectEmailStatus::Skipped, $prospect->emails()->where('step', 2)->first()->status);
        $this->assertSame(ProspectEmailStatus::Skipped, $prospect->emails()->where('step', 3)->first()->status);
        $this->assertSame(ProspectEmailStatus::Pending, $prospect->emails()->where('step', 1)->first()->status);
    }

    public function test_step_three_can_be_disabled_while_step_two_stays_active(): void
    {
        $prospect = $this->prospectWithSteps();

        app(SetProspectEmailStepAction::class)->sync($prospect, false, false, true);

        $prospect->refresh();

        $this->assertFalse($prospect->isStepDisabled(2));
        $this->assertTrue($prospect->isStepDisabled(3));
        $this->assertSame(ProspectEmailStatus::Pending, $prospect->emails()->where('step', 2)->first()->status);
        $this->assertSame(ProspectEmailStatus::Skipped, $prospect->emails()->where('step', 3)->first()->status);
    }

    public function test_existing_prospect_with_step_two_off_does_not_schedule_step_three(): void
    {
        $prospect = $this->prospectWithSteps();
        $prospect->update([
            'disable_step_2' => true,
            'disable_step_3' => false,
        ]);
        $stepTwo = $prospect->emails()->where('step', 2)->first();
        $stepTwo->update([
            'status' => ProspectEmailStatus::Sent,
            'opened_at' => now(),
        ]);
        $prospect->emails()->where('step', 3)->delete();

        app(ScheduleNextEmailAction::class)->afterEngagement($stepTwo->fresh());

        $this->assertTrue($prospect->fresh()->isStepDisabled(3));
        $this->assertFalse($prospect->emails()->where('step', 3)->exists());
    }

    public function test_new_prospect_keeps_step_three_active(): void
    {
        $category = Category::query()->create([
            'name' => 'Nuevo',
            'slug' => 'nuevo-paso',
            'search_query' => 'nuevo',
        ]);
        $prospect = Prospect::query()->create([
            'category_id' => $category->id,
            'email' => 'nuevo@example.org',
            'status' => ProspectStatus::InSequence,
        ]);

        $this->assertFalse($prospect->isStepDisabled(3));
    }

    private function prospectWithSteps(): Prospect
    {
        $category = Category::query()->create([
            'name' => 'Logística',
            'slug' => 'logistica-pasos',
            'search_query' => 'logistica',
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

        foreach ([1, 2, 3] as $step) {
            $template = EmailTemplate::query()->create([
                'name' => 'Paso '.$step,
                'step' => $step,
                'subject' => 'Hola',
                'body_html' => '<p>Hola</p>',
                'is_active' => true,
            ]);
            ProspectEmail::query()->create([
                'prospect_id' => $prospect->id,
                'prospect_sequence_id' => $sequence->id,
                'email_template_id' => $template->id,
                'step' => $step,
                'scheduled_at' => now()->addDay(),
                'status' => ProspectEmailStatus::Pending,
            ]);
        }

        return $prospect->fresh(['emails', 'sequence']);
    }
}
