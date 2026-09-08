<?php

declare(strict_types=1);

namespace App\Actions\Scraping;

use Illuminate\Support\Facades\Http;
use Throwable;

class FetchWebsiteAction
{
    public function execute(string $url): ?string
    {
        try {
            $response = Http::timeout((int) config('prospecting.scrape.website_timeout', 10))
                ->withHeaders([
                    'User-Agent' => 'PlanScrapingBot/1.0 (+https://jramirezr.com)',
                    'Accept' => 'text/html',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            return $response->body();
        } catch (Throwable) {
            return null;
        }
    }
}
