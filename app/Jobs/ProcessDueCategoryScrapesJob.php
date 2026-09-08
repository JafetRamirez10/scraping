<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Category;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDueCategoryScrapesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Category::query()
            ->dueForScrape()
            ->each(function (Category $category): void {
                ScrapeCategoryJob::dispatch($category);
            });
    }
}
