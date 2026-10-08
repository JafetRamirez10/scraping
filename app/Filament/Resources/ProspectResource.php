<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\Prospecting\SetProspectEmailStepAction;
use App\Enums\ProspectStatus;
use App\Filament\Resources\ProspectResource\Pages;
use App\Models\Prospect;
use App\Models\ProspectSequence;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                Tables\Columns\TextColumn::make('sequence.current_step')
                    ->label('Paso')
                    ->formatStateUsing(fn ($state) => $state ? "Paso {$state}" : '—')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            ProspectSequence::query()
                                ->select('current_step')
                                ->whereColumn('prospect_sequences.prospect_id', 'prospects.id')
                                ->limit(1),
                            $direction,
                        );
                    }),
                Tables\Columns\TextColumn::make('email_quality')
                    ->label('Calidad')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('disabled_steps')
                    ->label('Pasos off')
                    ->badge()
                    ->color('warning')
                    ->state(function (Prospect $record): ?string {
                        $disabled = [];

                        if ($record->disable_step_1) {
                            $disabled[] = '1';
                        }

                        if ($record->disable_step_2) {
                            $disabled[] = '2';
                        }

                        return $disabled === [] ? null : implode(', ', $disabled);
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Descubierto')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->searchDebounce('500ms')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->select([
                    'prospects.id',
                    'prospects.category_id',
                    'prospects.company_name',
                    'prospects.email',
                    'prospects.email_quality',
                    'prospects.status',
                    'prospects.disable_step_1',
                    'prospects.disable_step_2',
                    'prospects.created_at',
                ])
                ->with([
                    'category:id,name',
                    'sequence:id,prospect_id,current_step',
                ]))
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Categoría'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(ProspectStatus::cases())->mapWithKeys(
                        fn (ProspectStatus $case) => [$case->value => $case->getLabel()]
                    )->all()),
                Tables\Filters\SelectFilter::make('current_step')
                    ->label('Paso')
                    ->options([
                        1 => 'Paso 1',
                        2 => 'Paso 2',
                        3 => 'Paso 3',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q) => $q->whereHas(
                                'sequence',
                                fn (Builder $sequence) => $sequence->where('current_step', (int) $data['value'])
                            )
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('emailSteps')
                    ->label('Pasos')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->modalHeading('Pasos de este prospecto')
                    ->modalDescription('Aplica solo a este prospecto. Un paso ya enviado no se cancela.')
                    ->modalSubmitActionLabel('Guardar')
                    ->fillForm(fn (Prospect $record): array => [
                        'disable_step_1' => (bool) $record->disable_step_1,
                        'disable_step_2' => (bool) $record->disable_step_2,
                    ])
                    ->form([
                        Forms\Components\Toggle::make('disable_step_1')
                            ->label('Desactivar paso 1')
                            ->helperText('Si está pendiente, no se envía.'),
                        Forms\Components\Toggle::make('disable_step_2')
                            ->label('Desactivar paso 2')
                            ->helperText('Si está pendiente, no se envía. Si aún no existe, no se programa.'),
                    ])
                    ->action(function (Prospect $record, array $data): void {
                        app(SetProspectEmailStepAction::class)->sync(
                            $record,
                            (bool) ($data['disable_step_1'] ?? false),
                            (bool) ($data['disable_step_2'] ?? false),
                        );

                        Notification::make()
                            ->title('Pasos actualizados para este prospecto')
                            ->success()
                            ->send();
                    }),
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
