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

    public function handle(): void
    {
        // Close zombie runs left behind by killed/timed-out workers.
        ScrapeRun::query()
            ->where('status', ScrapeRunStatus::Running)
            ->where('started_at', '<=', now()->subHours(2))
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
                    ->where('started_at', '>', now()->subHours(2));
            })
            ->each(function (Category $category): void {
                ScrapeCategoryJob::dispatch($category);
            });
    }
}
