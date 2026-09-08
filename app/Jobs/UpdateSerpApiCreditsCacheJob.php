<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Search\SearchProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class UpdateSerpApiCreditsCacheJob implements ShouldQueue
{
    use Queueable;

    public function handle(SearchProviderInterface $searchProvider): void
    {
        $remaining = $searchProvider->getRemainingCredits();

        if ($remaining !== null) {
            Cache::put('serpapi_credits_remaining', $remaining, now()->addHours(6));
        }
    }
}
