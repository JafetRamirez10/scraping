<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Prospecting\StartProspectSequenceAction;
use App\Models\Prospect;
use App\Models\SuppressionListEntry;
use Illuminate\Console\Command;

class StartProspectSequencesCommand extends Command
{
    protected $signature = 'prospects:start
                            {ids* : One or more prospect IDs (space or comma separated)}';

    protected $description = 'Start email sequences for discovered prospects via StartProspectSequenceAction';

    public function handle(StartProspectSequenceAction $startSequence): int
    {
        $ids = collect($this->argument('ids'))
            ->flatMap(fn (string $value): array => preg_split('/[,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn (string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->error('Provide at least one valid prospect ID.');

            return self::FAILURE;
        }

        $started = 0;
        $already = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($ids as $id) {
            $prospect = Prospect::query()->find($id);

            if ($prospect === null) {
                $this->warn("id={$id} missing");
                $missing++;

                continue;
            }

            if ($prospect->sequence()->exists()) {
                $this->line("id={$id} already_active email={$prospect->email}");
                $already++;

                continue;
            }

            if (! $prospect->isContactable()) {
                $this->warn("id={$id} skipped reason=not_contactable status={$prospect->status->value}");
                $skipped++;

                continue;
            }

            if (SuppressionListEntry::query()->where('email', $prospect->email)->exists()) {
                $this->warn("id={$id} skipped reason=suppressed email={$prospect->email}");
                $skipped++;

                continue;
            }

            $sequence = $startSequence->execute($prospect->fresh());

            if ($sequence === null) {
                $this->warn("id={$id} skipped reason=action_returned_null email={$prospect->email}");
                $skipped++;

                continue;
            }

            $this->info("id={$id} started sequence_id={$sequence->id} email={$prospect->email}");
            $started++;
        }

        $this->newLine();
        $this->line("summary started={$started} already_active={$already} skipped={$skipped} missing={$missing}");

        return $missing > 0 && $started === 0 ? self::FAILURE : self::SUCCESS;
    }
}
