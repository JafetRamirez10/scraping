<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class ExtractedEmail
{
    public function __construct(
        public string $email,
        public string $source,
        public string $quality,
    ) {}
}
