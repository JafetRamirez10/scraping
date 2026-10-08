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
                Tables\Columns\TextColumn::make('active_steps')
                    ->label('Pasos activos')
                    ->badge()
                    ->color(fn (Prospect $record): string => ($record->disable_step_1 || $record->disable_step_2) ? 'warning' : 'success')
                    ->state(function (Prospect $record): string {
                        $active = [];

                        if (! $record->disable_step_1) {
                            $active[] = '1';
                        }

                        if (! $record->disable_step_2) {
                            $active[] = '2';
                        }

                        return $active === [] ? 'Ninguno' : implode(', ', $active);
                    }),
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
                    ->modalDescription('Por defecto los dos pasos están activos. Apaga uno solo para este prospecto. Un paso ya enviado no se cancela.')
                    ->modalSubmitActionLabel('Guardar')
                    ->fillForm(fn (Prospect $record): array => [
                        'step_1_active' => ! $record->disable_step_1,
                        'step_2_active' => ! $record->disable_step_2,
                    ])
                    ->form([
                        Forms\Components\Toggle::make('step_1_active')
                            ->label('Paso 1 activo')
                            ->default(true)
                            ->helperText('Encendido: se envía si está pendiente.'),
                        Forms\Components\Toggle::make('step_2_active')
                            ->label('Paso 2 activo')
                            ->default(true)
                            ->helperText('Encendido: se envía o se programa. Apagado: no sale para este prospecto.'),
                    ])
                    ->action(function (Prospect $record, array $data): void {
                        app(SetProspectEmailStepAction::class)->sync(
                            $record,
                            ! (bool) ($data['step_1_active'] ?? true),
                            ! (bool) ($data['step_2_active'] ?? true),
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
