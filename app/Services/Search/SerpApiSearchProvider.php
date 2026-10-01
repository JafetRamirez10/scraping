<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\DataTransferObjects\SearchResponse;
use App\DataTransferObjects\SearchResultItem;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use SerpApi\Client;
use SerpApi\SerpApiException;
use Throwable;

class SerpApiSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function search(Category $category): SearchResponse
    {
        $pages = max(1, (int) config('services.serpapi.pages', 5));
        $numResults = max(1, (int) config('services.serpapi.num_results', 10));

        $items = [];
        $seenUrls = [];
        $searchId = null;

        try {
            for ($page = 0; $page < $pages; $page++) {
                $start = $page * $numResults;

                $results = $this->client->search([
                    'engine' => config('services.serpapi.engine', 'google'),
                    'q' => $category->search_query,
                    'location' => config('services.serpapi.location', 'Mexico'),
                    'gl' => config('services.serpapi.gl', 'mx'),
                    'hl' => config('services.serpapi.hl', 'es'),
                    'num' => $numResults,
                    'start' => $start,
                ]);

                if ($searchId === null && isset($results->search_metadata->id)) {
                    $searchId = (string) $results->search_metadata->id;
                }

                $pageItems = $this->parseOrganicResults($results);

                if ($pageItems === []) {
                    break;
                }

                foreach ($pageItems as $item) {
                    $normalizedUrl = rtrim(strtolower($item->url), '/');

                    if (isset($seenUrls[$normalizedUrl])) {
                        continue;
                    }

                    $seenUrls[$normalizedUrl] = true;
                    $items[] = $item;
                }
            }

            return new SearchResponse(
                items: $items,
                searchId: $searchId,
            );
        } catch (SerpApiException $exception) {
            Log::error('SerpAPI search failed', [
                'category_id' => $category->id,
                'status' => $exception->getResponseStatus(),
                'search_id' => $exception->getSearchId(),
            ]);

            throw $exception;
        }
    }

    public function getRemainingCredits(): ?int
    {
        try {
            $account = $this->client->account();
            $plan = $account->plan ?? null;

            if ($plan === null) {
                return null;
            }

            $searchesPerMonth = (int) ($plan->searches_per_month ?? 0);
            $used = (int) ($account->total_searches_this_month ?? 0);

            return max(0, $searchesPerMonth - $used);
        } catch (Throwable $exception) {
            Log::warning('Unable to fetch SerpAPI account info', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function recoverFromArchive(string $searchId): array
    {
        $results = $this->client->searchArchive($searchId);

        return $this->parseOrganicResults($results);
    }

    /**
     * @return array<int, SearchResultItem>
     */
    private function parseOrganicResults(object $results): array
    {
        $items = [];

        foreach ($results->organic_results ?? [] as $result) {
            if (! isset($result->link)) {
                continue;
            }

            $items[] = new SearchResultItem(
                title: (string) ($result->title ?? ''),
                url: (string) $result->link,
                snippet: isset($result->snippet) ? (string) $result->snippet : null,
            );
        }

        return $items;
    }
}
