<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ScrapeRunStatus;
use App\Models\Category;
use App\Models\ScrapeRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDueCategoryScrapesJob implements ShouldQueue
{
    use Queueable;

    /** Running scrapes older than this are treated as stuck and ignored for dispatch. */
    private const RUNNING_SCRAPE_GRACE_MINUTES = 30;

    public function handle(): void
    {
        // Close zombie runs left behind by killed/timed-out workers.
        ScrapeRun::query()
            ->where('status', ScrapeRunStatus::Running)
            ->where('started_at', '<=', now()->subMinutes(self::RUNNING_SCRAPE_GRACE_MINUTES))
            ->update([
                'status' => ScrapeRunStatus::Failed,
                'error_message' => 'Scrape abandoned (stuck running)',
                'finished_at' => now(),
            ]);

        Category::query()
            ->dueForScrape()
            ->whereDoesntHave('scrapeRuns', function ($query): void {
                $query
                    ->where('status', ScrapeRunStatus::Running)
                    ->where('started_at', '>', now()->subMinutes(self::RUNNING_SCRAPE_GRACE_MINUTES));
            })
            ->each(function (Category $category): void {
                ScrapeCategoryJob::dispatch($category);
            });
    }
}
