<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\DataTransferObjects\SearchResponse;
use App\DataTransferObjects\SearchResultItem;
use App\Models\Category;

interface SearchProviderInterface
{
    public function search(Category $category): SearchResponse;

    public function getRemainingCredits(): ?int;

    /**
     * @return array<int, SearchResultItem>
     */
    public function recoverFromArchive(string $searchId): array;
}
