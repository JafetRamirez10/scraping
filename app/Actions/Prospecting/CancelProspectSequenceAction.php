<?php

declare(strict_types=1);

namespace App\Actions\Prospecting;

use App\Enums\ProspectEmailStatus;
use App\Enums\ProspectStatus;
use App\Enums\SequenceCancelReason;
use App\Enums\SuppressionReason;
use App\Models\Prospect;
use App\Models\ProspectEmail;
use App\Models\SuppressionListEntry;
use Illuminate\Support\Facades\DB;

class CancelProspectSequenceAction
{
    public function execute(
        Prospect $prospect,
        SequenceCancelReason $reason,
        ?ProspectStatus $prospectStatus = null,
    ): void {
        DB::transaction(function () use ($prospect, $reason, $prospectStatus): void {
            $sequence = $prospect->sequence;

            if ($sequence !== null && $sequence->isActive()) {
                $sequence->update([
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                    'completed_at' => now(),
                ]);

                ProspectEmail::query()
                    ->where('prospect_sequence_id', $sequence->id)
                    ->where('status', ProspectEmailStatus::Pending)
                    ->update([
                        'status' => ProspectEmailStatus::Skipped,
                        'error_message' => 'Sequence cancelled: ' . $reason->value,
                    ]);
            }

            if ($prospectStatus !== null) {
                $prospect->update(['status' => $prospectStatus]);
            }
        });
    }

    public function addToSuppressionList(string $email, SuppressionReason $reason): void
    {
        SuppressionListEntry::query()->updateOrCreate(
            ['email' => strtolower($email)],
            [
                'reason' => $reason,
                'suppressed_at' => now(),
            ],
        );
    }
}
