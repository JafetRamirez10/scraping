<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ScrapeRunResource\Pages;
use App\Models\ScrapeRun;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScrapeRunResource extends Resource
{
    protected static ?string $model = ScrapeRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Prospección';

    protected static ?string $modelLabel = 'Ejecución scrape';

    protected static ?string $pluralModelLabel = 'Historial scrapes';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')->label('Categoría'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('emails_saved')->label('Guardados'),
                Tables\Columns\TextColumn::make('serpapi_search_id')->label('SerpAPI ID')->limit(20),
                Tables\Columns\TextColumn::make('started_at')->dateTime(),
                Tables\Columns\TextColumn::make('finished_at')->dateTime(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScrapeRuns::route('/'),
            'view' => Pages\ViewScrapeRun::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
