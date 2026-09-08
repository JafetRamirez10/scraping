<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProspectResource\Pages;
use App\Models\Prospect;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Prospección';

    protected static ?string $modelLabel = 'Prospecto';

    protected static ?string $pluralModelLabel = 'Prospectos';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Empresa')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Categoría'),
                Tables\Columns\TextColumn::make('email_quality')
                    ->label('Calidad')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Descubierto')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Categoría'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(\App\Enums\ProspectStatus::cases())->mapWithKeys(
                        fn (\App\Enums\ProspectStatus $case) => [$case->value => $case->getLabel()]
                    )->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspects::route('/'),
            'view' => Pages\ViewProspect::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
