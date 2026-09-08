<?php

declare(strict_types=1);

namespace App\Enums;

enum ScrapeRunUrlStatus: string
{
    case Pending = 'pending';
    case Visited = 'visited';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
