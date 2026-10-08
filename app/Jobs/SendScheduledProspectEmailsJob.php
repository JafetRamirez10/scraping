<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Prospecting\SendProspectEmailAction;
use App\Enums\ProspectEmailStatus;
use App\Models\ProspectEmail;
use App\Services\Prospecting\EmailSendWindowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendScheduledProspectEmailsJob implements ShouldQueue
{
    use Queueable;

    public function handle(
        SendProspectEmailAction $sendAction,
        EmailSendWindowService $sendWindow,
    ): void {
        if (! $sendWindow->isWithinSendWindow()) {
            return;
        }

        if (! $sendWindow->canSendMoreToday()) {
            return;
        }

        $remaining = (int) config('prospecting.email.daily_limit', 10) - $sendWindow->sentCountToday();

        ProspectEmail::query()
            ->with(['prospect.category', 'template', 'sequence'])
            ->where('status', ProspectEmailStatus::Pending)
            ->where('scheduled_at', '<=', now())
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('step', '!=', 1)
                        ->orWhereHas('prospect', fn ($prospect) => $prospect->where('disable_step_1', false));
                })->where(function ($query): void {
                    $query->where('step', '!=', 2)
                        ->orWhereHas('prospect', fn ($prospect) => $prospect->where('disable_step_2', false));
                });
            })
            ->orderBy('scheduled_at')
            ->limit(max(0, $remaining))
            ->each(function (ProspectEmail $prospectEmail) use ($sendAction, $sendWindow): void {
                if (! $sendWindow->canSendMoreToday()) {
                    return;
                }

                $sendAction->execute($prospectEmail);
            });
    }
}
