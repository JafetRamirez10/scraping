<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\EmailQuality;
use App\Enums\ProspectEmailStatus;
use App\Models\EmailTemplate;
use App\Models\ProspectEmail;
use App\Services\Prospecting\EmailSendWindowService;
use Illuminate\Support\Facades\DB;

class ScheduleNextEmailAction
{
    public function __construct(
        private readonly EmailSendWindowService $sendWindowService,
    ) {}

    public function afterOpen(ProspectEmail $prospectEmail): void
    {
        if ($prospectEmail->step !== 1) {
            return;
        }

        $prospect = $prospectEmail->prospect;

        if ($prospect->email_quality === EmailQuality::Low->value) {
            return;
        }

        if ($prospectEmail->sequence->emails()->where('step', 2)->exists()) {
            return;
        }

        $days = (int) config('prospecting.sequence.step2_schedule_days_after_open', 5);
        $template = EmailTemplate::query()->where('step', 2)->where('is_active', true)->first();

        if ($template === null) {
            return;
        }

        ProspectEmail::query()->create([
            'prospect_id' => $prospect->id,
            'prospect_sequence_id' => $prospectEmail->prospect_sequence_id,
            'email_template_id' => $template->id,
            'step' => 2,
            'scheduled_at' => $this->sendWindowService->nextAvailableSendTime(
                now()->addDays($days)
            ),
            'status' => ProspectEmailStatus::Pending,
        ]);
    }

    public function afterEngagement(ProspectEmail $prospectEmail): void
    {
        if ($prospectEmail->step !== 2) {
            return;
        }

        if ($prospectEmail->sequence->emails()->where('step', 3)->exists()) {
            return;
        }

        $days = (int) config('prospecting.sequence.step3_schedule_days_after_step2', 15);
        $template = EmailTemplate::query()->where('step', 3)->where('is_active', true)->first();

        if ($template === null) {
            return;
        }

        ProspectEmail::query()->create([
            'prospect_id' => $prospectEmail->prospect_id,
            'prospect_sequence_id' => $prospectEmail->prospect_sequence_id,
            'email_template_id' => $template->id,
            'step' => 3,
            'scheduled_at' => $this->sendWindowService->nextAvailableSendTime(
                now()->addDays($days)
            ),
            'status' => ProspectEmailStatus::Pending,
        ]);
    }
}
