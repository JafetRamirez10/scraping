<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectEmailStatus;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Services\Prospecting\EmailSendWindowService;

class SetProspectEmailStepAction
{
    public const DISABLED_MESSAGE = 'step_disabled';

    public function __construct(
        private readonly EmailSendWindowService $sendWindowService,
        private readonly ScheduleNextEmailAction $scheduleNextEmail,
    ) {}

    public function sync(Prospect $prospect, bool $disableStep1, bool $disableStep2): void
    {
        $wasStep1 = (bool) $prospect->disable_step_1;
        $wasStep2 = (bool) $prospect->disable_step_2;

        $prospect->update([
            'disable_step_1' => $disableStep1,
            'disable_step_2' => $disableStep2,
        ]);

        $prospect->refresh();

        if ($disableStep1) {
            $this->skipPending($prospect, 1);
        } elseif ($wasStep1) {
            $this->restore($prospect, 1);
        }

        if ($disableStep2) {
            $this->skipPending($prospect, 2);
        } elseif ($wasStep2) {
            $this->restore($prospect, 2);
        }
    }

    /**
     * @return array{status: ProspectEmailStatus, error_message: string|null}
     */
    public static function initialAttributes(Prospect $prospect, int $step): array
    {
        if (! $prospect->isStepDisabled($step)) {
            return [
                'status' => ProspectEmailStatus::Pending,
                'error_message' => null,
            ];
        }

        return [
            'status' => ProspectEmailStatus::Skipped,
            'error_message' => self::DISABLED_MESSAGE,
        ];
    }

    private function skipPending(Prospect $prospect, int $step): void
    {
        ProspectEmail::query()
            ->where('prospect_id', $prospect->id)
            ->where('step', $step)
            ->where('status', ProspectEmailStatus::Pending)
            ->update([
                'status' => ProspectEmailStatus::Skipped,
                'error_message' => self::DISABLED_MESSAGE,
            ]);
    }

    private function restore(Prospect $prospect, int $step): void
    {
        $email = ProspectEmail::query()
            ->where('prospect_id', $prospect->id)
            ->where('step', $step)
            ->first();

        if ($email !== null) {
            if ($email->status === ProspectEmailStatus::Skipped && $email->error_message === self::DISABLED_MESSAGE) {
                $email->update([
                    'status' => ProspectEmailStatus::Pending,
                    'error_message' => null,
                    'scheduled_at' => $this->sendWindowService->nextAvailableSendTime(),
                ]);
            }

            return;
        }

        if ($step !== 2) {
            return;
        }

        $stepOne = ProspectEmail::query()
            ->where('prospect_id', $prospect->id)
            ->where('step', 1)
            ->first();

        if ($stepOne?->opened_at === null) {
            return;
        }

        $this->scheduleNextEmail->afterOpen($stepOne);
    }
}
