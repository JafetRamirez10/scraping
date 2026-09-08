<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\EmailQuality;
use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectStatus;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Models\ProspectSequence;
use App\Models\SuppressionListEntry;
use App\Services\Prospecting\EmailSendWindowService;
use Illuminate\Support\Facades\DB;

class StartProspectSequenceAction
{
    public function __construct(
        private readonly EmailSendWindowService $sendWindowService,
    ) {}

    public function execute(Prospect $prospect): ?ProspectSequence
    {
        if (! $prospect->isContactable()) {
            return null;
        }

        if (SuppressionListEntry::query()->where('email', $prospect->email)->exists()) {
            return null;
        }

        if ($prospect->sequence()->exists()) {
            return $prospect->sequence;
        }

        if ($prospect->email_quality === EmailQuality::Low->value) {
            return $this->startSingleStepSequence($prospect);
        }

        return DB::transaction(function () use ($prospect): ProspectSequence {
            $sequence = ProspectSequence::query()->create([
                'prospect_id' => $prospect->id,
                'current_step' => 1,
                'started_at' => now(),
            ]);

            $template = EmailTemplate::query()
                ->where('step', 1)
                ->where('is_active', true)
                ->firstOrFail();

            ProspectEmail::query()->create([
                'prospect_id' => $prospect->id,
                'prospect_sequence_id' => $sequence->id,
                'email_template_id' => $template->id,
                'step' => 1,
                'scheduled_at' => $this->sendWindowService->nextAvailableSendTime(),
                'status' => ProspectEmailStatus::Pending,
            ]);

            $prospect->update(['status' => ProspectStatus::InSequence]);

            return $sequence;
        });
    }

    private function startSingleStepSequence(Prospect $prospect): ?ProspectSequence
    {
        return DB::transaction(function () use ($prospect): ProspectSequence {
            $sequence = ProspectSequence::query()->create([
                'prospect_id' => $prospect->id,
                'current_step' => 1,
                'started_at' => now(),
            ]);

            $template = EmailTemplate::query()
                ->where('step', 1)
                ->where('is_active', true)
                ->firstOrFail();

            ProspectEmail::query()->create([
                'prospect_id' => $prospect->id,
                'prospect_sequence_id' => $sequence->id,
                'email_template_id' => $template->id,
                'step' => 1,
                'scheduled_at' => $this->sendWindowService->nextAvailableSendTime(),
                'status' => ProspectEmailStatus::Pending,
            ]);

            $prospect->update(['status' => ProspectStatus::InSequence]);

            return $sequence;
        });
    }
}
