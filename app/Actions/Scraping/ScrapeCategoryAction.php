<?php

declare(strict_types=1);

namespace App\Actions\Scraping;

use App\Actions\Prospecting\StartProspectSequenceAction;
use App\DataTransferObjects\ExtractedEmail;
use App\DataTransferObjects\SearchResultItem;
use App\Enums\ProspectStatus;
use App\Enums\ScrapeRunStatus;
use App\Enums\ScrapeRunUrlStatus;
use App\Models\Category;
use App\Models\Prospect;
use App\Models\ScrapeRun;
use App\Models\ScrapeRunUrl;
use App\Models\SuppressionListEntry;
use App\Services\Search\SearchProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapeCategoryAction
{
    public function __construct(
        private readonly SearchProviderInterface $searchProvider,
        private readonly FetchWebsiteAction $fetchWebsite,
        private readonly ExtractEmailsFromHtmlAction $extractEmails,
        private readonly ValidateEmailAction $validateEmail,
        private readonly StartProspectSequenceAction $startSequence,
    ) {}

    public function execute(Category $category): ScrapeRun
    {
        $run = ScrapeRun::query()->create([
            'category_id' => $category->id,
            'status' => ScrapeRunStatus::Running,
            'started_at' => now(),
        ]);

        $savedCount = 0;
        $target = $category->target_email_count;

        try {
            $searchResponse = $this->searchProvider->search($category);
            $searchResults = $searchResponse->items;

            $run->update(['serpapi_search_id' => $searchResponse->searchId]);

            foreach ($searchResults as $result) {
                if ($savedCount >= $target) {
                    break;
                }

                $savedFromResult = $this->processSearchResult($category, $run, $result, $target - $savedCount);
                $savedCount += $savedFromResult;

                sleep((int) config('prospecting.scrape.delay_seconds', 3));
            }

            $category->update([
                'last_scraped_at' => now(),
                'next_scrape_at' => now()->addDays($category->scrape_interval_days),
            ]);

            $run->update([
                'status' => ScrapeRunStatus::Completed,
                'emails_saved' => $savedCount,
                'emails_found' => $savedCount,
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Scrape category failed', [
                'category_id' => $category->id,
                'scrape_run_id' => $run->id,
                'message' => $exception->getMessage(),
            ]);

            $run->update([
                'status' => ScrapeRunStatus::Failed,
                'error_message' => mb_substr($exception->getMessage() ?: 'Scrape failed', 0, 250),
                'finished_at' => now(),
            ]);

            // Avoid hammering the same due category every scheduler tick after failures.
            $category->update([
                'next_scrape_at' => now()->addHour(),
            ]);
        }

        return $run->fresh();
    }

    private function processSearchResult(
        Category $category,
        ScrapeRun $run,
        SearchResultItem $result,
        int $remaining,
    ): int {
        $runUrl = ScrapeRunUrl::query()->create([
            'scrape_run_id' => $run->id,
            'url' => $result->url,
            'status' => ScrapeRunUrlStatus::Pending,
        ]);

        $extracted = $this->extractEmails->extractFromSnippet($result->snippet);
        $html = $this->fetchWebsite->execute($result->url);

        if ($html !== null) {
            $extracted = array_merge($extracted, $this->extractEmails->execute($html, $result->url));
        }

        $saved = 0;

        foreach ($extracted as $emailData) {
            if ($saved >= $remaining) {
                break;
            }

            if ($this->persistProspect($category, $result, $emailData)) {
                $saved++;
            }
        }

        $runUrl->update([
            'status' => $html === null && $saved === 0
                ? ScrapeRunUrlStatus::Failed
                : ScrapeRunUrlStatus::Visited,
            'emails_extracted' => $saved,
        ]);

        return $saved;
    }

    private function persistProspect(
        Category $category,
        SearchResultItem $result,
        ExtractedEmail $emailData,
    ): bool {
        if (! $this->validateEmail->execute($emailData->email)) {
            return false;
        }

        if (Prospect::query()->where('email', $emailData->email)->exists()) {
            return false;
        }

        if (SuppressionListEntry::query()->where('email', $emailData->email)->exists()) {
            return false;
        }

        $prospect = DB::transaction(function () use ($category, $result, $emailData): Prospect {
            return Prospect::query()->create([
                'category_id' => $category->id,
                'company_name' => $result->title !== '' ? $result->title : null,
                'website_url' => $result->url,
                'email' => $emailData->email,
                'email_quality' => $emailData->quality,
                'email_source' => $emailData->source,
                'status' => ProspectStatus::Discovered,
                'metadata' => [
                    'title' => $result->title,
                    'url' => $result->url,
                    'snippet' => $result->snippet,
                ],
            ]);
        });

        $this->startSequence->execute($prospect);

        return true;
    }
}
