<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Scraping\ScrapeCategoryAction;
use App\Enums\ScrapeRunStatus;
use App\Models\Category;
use App\Models\ScrapeRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ScrapeCategoryJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** Allow multi-page SerpAPI + website fetches. Must stay below queue retry_after. */
    public int $timeout = 900;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly Category $category,
    ) {}

    public function handle(ScrapeCategoryAction $action): void
    {
        $action->execute($this->category);
    }

    public function failed(?Throwable $exception): void
    {
        ScrapeRun::query()
            ->where('category_id', $this->category->id)
            ->where('status', ScrapeRunStatus::Running)
            ->update([
                'status' => ScrapeRunStatus::Failed,
                'error_message' => $exception?->getMessage()
                    ? mb_substr($exception->getMessage(), 0, 250)
                    : 'Job timed out or failed',
                'finished_at' => now(),
            ]);

        $this->category->update([
            'next_scrape_at' => now()->addHour(),
        ]);
    }
}
