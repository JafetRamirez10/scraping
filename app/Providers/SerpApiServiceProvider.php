<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Search\SearchProviderInterface;
use App\Services\Search\SerpApiSearchProvider;
use Illuminate\Support\ServiceProvider;
use SerpApi\Client;

class SerpApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function (): Client {
            return new Client(
                (string) config('services.serpapi.key'),
                (string) config('services.serpapi.engine', 'google'),
                (int) config('services.serpapi.timeout', 60),
            );
        });

        $this->app->bind(SearchProviderInterface::class, SerpApiSearchProvider::class);
    }
}
