<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class SearchResultItem
{
    public function __construct(
        public string $title,
        public string $url,
        public ?string $snippet = null,
    ) {}
}
