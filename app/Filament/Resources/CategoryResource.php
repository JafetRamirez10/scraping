<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Jobs\ScrapeCategoryJob;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prospección';

    protected static ?string $modelLabel = 'Categoría';

    protected static ?string $pluralModelLabel = 'Categorías';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(150),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('search_query')
                ->label('Query de búsqueda')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('target_email_count')
                ->label('Correos objetivo')
                ->numeric()
                ->minValue(1)
                ->maxValue(50)
                ->default(10)
                ->required(),
            Forms\Components\TextInput::make('scrape_interval_days')
                ->label('Intervalo (días)')
                ->numeric()
                ->minValue(1)
                ->default(2)
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Activa')
                ->default(true),
            Forms\Components\DateTimePicker::make('next_scrape_at')
                ->label('Próximo scrape')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('search_query')->label('Query')->limit(40),
                Tables\Columns\IconColumn::make('is_active')->label('Activa')->boolean(),
                Tables\Columns\TextColumn::make('last_scraped_at')->label('Último scrape')->dateTime(),
                Tables\Columns\TextColumn::make('next_scrape_at')->label('Próximo scrape')->dateTime(),
                Tables\Columns\TextColumn::make('prospects_count')->counts('prospects')->label('Prospectos'),
            ])
            ->actions([
                Tables\Actions\Action::make('scrapeNow')
                    ->label('Scrapear ahora')
                    ->icon('heroicon-o-magnifying-glass')
                    ->requiresConfirmation()
                    ->action(function (Category $record): void {
                        ScrapeCategoryJob::dispatch($record);

                        Notification::make()
                            ->title('Scrape encolado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
