<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScrapeRunResource\Pages;

use App\Filament\Resources\ScrapeRunResource;
use Filament\Resources\Pages\ListRecords;

class ListScrapeRuns extends ListRecords
{
    protected static string $resource = ScrapeRunResource::class;
}
