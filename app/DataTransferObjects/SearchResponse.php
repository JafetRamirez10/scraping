<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class SearchResponse
{
    /**
     * @param array<int, SearchResultItem> $items
     */
    public function __construct(
        public array $items,
        public ?string $searchId = null,
    ) {}
}
