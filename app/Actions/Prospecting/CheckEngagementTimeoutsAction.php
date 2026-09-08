<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectStatus;
use App\Enums\SequenceCancelReason;
use App\Enums\SuppressionReason;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Services\Prospecting\EmailSendWindowService;

class CheckEngagementTimeoutsAction
{
    public function __construct(
        private readonly CancelProspectSequenceAction $cancelSequence,
        private readonly EmailSendWindowService $sendWindowService,
    ) {}

    public function execute(): void
    {
        $openWindowDays = (int) config('prospecting.sequence.step1_open_window_days', 5);
        $engagementWindowDays = (int) config('prospecting.sequence.step2_engagement_window_days', 7);

        ProspectEmail::query()
            ->with(['prospect', 'sequence'])
            ->where('step', 1)
            ->where('status', ProspectEmailStatus::Sent)
            ->whereNull('opened_at')
            ->where('sent_at', '<=', now()->subDays($openWindowDays))
            ->each(function (ProspectEmail $email): void {
                $this->cancelSequence->execute(
                    $email->prospect,
                    SequenceCancelReason::NoEngagement,
                    ProspectStatus::NoEngagement,
                );
            });

        ProspectEmail::query()
            ->with(['prospect', 'sequence'])
            ->where('step', 2)
            ->where('status', ProspectEmailStatus::Sent)
            ->whereNull('opened_at')
            ->whereNull('clicked_at')
            ->where('sent_at', '<=', now()->subDays($engagementWindowDays))
            ->each(function (ProspectEmail $email): void {
                $this->cancelSequence->execute(
                    $email->prospect,
                    SequenceCancelReason::NoEngagement,
                    ProspectStatus::SequenceCompleted,
                );
            });
    }
}
