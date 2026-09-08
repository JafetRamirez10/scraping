<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScrapeRunResource\Pages;

use App\Filament\Resources\ScrapeRunResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewScrapeRun extends ViewRecord
{
    protected static string $resource = ScrapeRunResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()->schema([
                TextEntry::make('category.name')->label('Categoría'),
                TextEntry::make('status')->badge(),
                TextEntry::make('emails_found')->label('Encontrados'),
                TextEntry::make('emails_saved')->label('Guardados'),
                TextEntry::make('serpapi_search_id')->label('SerpAPI search ID'),
                TextEntry::make('error_message')->label('Error'),
                TextEntry::make('started_at')->dateTime(),
                TextEntry::make('finished_at')->dateTime(),
            ])->columns(2),
        ]);
    }
}
