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

class HandleBrevoWebhookAction
{
    public function __construct(
        private readonly ScheduleNextEmailAction $scheduleNextEmail,
        private readonly CancelProspectSequenceAction $cancelSequence,
        private readonly EmailSendWindowService $sendWindowService,
    ) {}

    public function execute(string $event, array $payload): void
    {
        $email = strtolower((string) ($payload['email'] ?? ''));

        if ($email === '') {
            return;
        }

        $prospect = Prospect::query()->where('email', $email)->first();

        if ($prospect === null) {
            return;
        }

        match ($event) {
            'opened', 'unique_opened' => $this->handleOpened($prospect, $payload),
            'click', 'unique_click' => $this->handleClicked($prospect, $payload),
            'hard_bounce', 'soft_bounce', 'blocked' => $this->handleBounce($prospect),
            'spam', 'complaint' => $this->handleComplaint($prospect),
            'unsubscribed' => $this->handleUnsubscribe($prospect),
            default => null,
        };
    }

    private function handleOpened(Prospect $prospect, array $payload): void
    {
        $prospectEmail = $this->findLatestSentEmail($prospect, 2)
            ?? $this->findLatestSentEmail($prospect, 1);

        if ($prospectEmail === null || $prospectEmail->opened_at !== null) {
            return;
        }

        $prospectEmail->update(['opened_at' => now()]);

        if ($prospectEmail->step === 1) {
            $this->scheduleNextEmail->afterOpen($prospectEmail);
        }

        if ($prospectEmail->step === 2) {
            $this->scheduleNextEmail->afterEngagement($prospectEmail);
        }
    }

    private function handleClicked(Prospect $prospect, array $payload): void
    {
        $prospectEmail = $this->findLatestSentEmail($prospect, 2)
            ?? $this->findLatestSentEmail($prospect, 1);

        if ($prospectEmail === null) {
            return;
        }

        if ($prospectEmail->clicked_at === null) {
            $prospectEmail->update(['clicked_at' => now()]);
        }

        if ($prospectEmail->step === 2) {
            $this->scheduleNextEmail->afterEngagement($prospectEmail);
        } elseif ($prospectEmail->step === 1 && $prospectEmail->opened_at === null) {
            $prospectEmail->update(['opened_at' => now()]);
            $this->scheduleNextEmail->afterOpen($prospectEmail);
        }
    }

    private function handleBounce(Prospect $prospect): void
    {
        $prospect->increment('bounce_count');

        $this->cancelSequence->addToSuppressionList($prospect->email, SuppressionReason::Bounce);
        $this->cancelSequence->execute(
            $prospect,
            SequenceCancelReason::Bounce,
            ProspectStatus::Bounced,
        );

        $this->sendWindowService->incrementDailyStat('bounced_count');
    }

    private function handleComplaint(Prospect $prospect): void
    {
        $this->cancelSequence->addToSuppressionList($prospect->email, SuppressionReason::Complaint);
        $this->cancelSequence->execute(
            $prospect,
            SequenceCancelReason::Complaint,
            ProspectStatus::Unsubscribed,
        );

        $prospect->update(['unsubscribed_at' => now()]);
        $this->sendWindowService->incrementDailyStat('complaint_count');
    }

    private function handleUnsubscribe(Prospect $prospect): void
    {
        $this->cancelSequence->addToSuppressionList($prospect->email, SuppressionReason::Unsubscribe);
        $this->cancelSequence->execute(
            $prospect,
            SequenceCancelReason::Unsubscribe,
            ProspectStatus::Unsubscribed,
        );

        $prospect->update(['unsubscribed_at' => now()]);
    }

    private function findLatestSentEmail(Prospect $prospect, int $step): ?ProspectEmail
    {
        return ProspectEmail::query()
            ->where('prospect_id', $prospect->id)
            ->where('step', $step)
            ->where('status', ProspectEmailStatus::Sent)
            ->latest('sent_at')
            ->first();
    }
}
