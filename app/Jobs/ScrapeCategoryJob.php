<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Scraping\ScrapeCategoryAction;
use App\Models\Category;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeCategoryJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly Category $category,
    ) {}

    public function handle(ScrapeCategoryAction $action): void
    {
        $action->execute($this->category);
    }
}
