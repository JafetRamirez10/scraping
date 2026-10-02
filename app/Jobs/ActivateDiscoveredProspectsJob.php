<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Prospecting\StartProspectSequenceAction;
use App\Enums\ProspectStatus;
use App\Models\Prospect;
use App\Models\SuppressionListEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ActivateDiscoveredProspectsJob implements ShouldQueue
{
    use Queueable;

    public function handle(StartProspectSequenceAction $startSequence): void
    {
        $batch = max(1, (int) config('prospecting.sequence.activate_discovered_batch', 50));

        $prospects = Prospect::query()
            ->where('status', ProspectStatus::Discovered)
            ->whereDoesntHave('sequence')
            ->orderBy('id')
            ->limit($batch)
            ->get();

        if ($prospects->isEmpty()) {
            return;
        }

        $started = 0;
        $skipped = 0;

        foreach ($prospects as $prospect) {
            if (SuppressionListEntry::query()->where('email', $prospect->email)->exists()) {
                $skipped++;

                continue;
            }

            if (! $prospect->isContactable()) {
                $skipped++;

                continue;
            }

            $sequence = $startSequence->execute($prospect);

            if ($sequence === null) {
                $skipped++;

                continue;
            }

            $started++;
        }

        Log::info('ActivateDiscoveredProspectsJob finished', [
            'candidates' => $prospects->count(),
            'started' => $started,
            'skipped' => $skipped,
        ]);
    }
}
