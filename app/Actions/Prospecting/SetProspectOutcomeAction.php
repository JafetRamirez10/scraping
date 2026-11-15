<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectOutcome;
use App\Enums\SequenceCancelReason;
use App\Models\Prospect;

class SetProspectOutcomeAction
{
    public function __construct(
        private readonly CancelProspectSequenceAction $cancelSequence,
    ) {}

    public function execute(Prospect $prospect, ?ProspectOutcome $outcome): void
    {
        $prospect->update(['outcome' => $outcome]);
        $prospect->refresh();

        if ($outcome === null || ! $outcome->stopsEmails()) {
            return;
        }

        $this->cancelSequence->execute($prospect, SequenceCancelReason::FollowUpStopped);
    }
}
