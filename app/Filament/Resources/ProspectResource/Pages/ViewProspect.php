<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Actions\Prospecting\SetProspectEmailStepAction;
use App\Filament\Resources\ProspectResource;
use App\Models\Prospect;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProspect extends ViewRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('emailSteps')
                ->label('Pasos')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modalHeading('Pasos de este prospecto')
                ->modalDescription('Por defecto los dos pasos están activos. Apaga uno solo para este prospecto. Un paso ya enviado no se cancela.')
                ->modalSubmitActionLabel('Guardar')
                ->fillForm(fn (): array => [
                    'step_1_active' => ! (bool) $this->getRecord()->disable_step_1,
                    'step_2_active' => ! (bool) $this->getRecord()->disable_step_2,
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
                ->action(function (array $data): void {
                    /** @var Prospect $prospect */
                    $prospect = $this->getRecord();

                    app(SetProspectEmailStepAction::class)->sync(
                        $prospect,
                        ! (bool) ($data['step_1_active'] ?? true),
                        ! (bool) ($data['step_2_active'] ?? true),
                    );

                    Notification::make()
                        ->title('Pasos actualizados para este prospecto')
                        ->success()
                        ->send();

                    $this->redirect(ProspectResource::getUrl('view', ['record' => $prospect]));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Prospecto')->schema([
                TextEntry::make('company_name')->label('Empresa'),
                TextEntry::make('email'),
                TextEntry::make('category.name')->label('Categoría'),
                TextEntry::make('website_url')->label('Sitio web')->url(fn ($record) => $record->website_url),
                TextEntry::make('status')->badge(),
                TextEntry::make('email_quality')->label('Calidad'),
                TextEntry::make('email_source')->label('Fuente'),
                IconEntry::make('step_1_active')
                    ->label('Paso 1 activo')
                    ->state(fn (Prospect $record): bool => ! $record->disable_step_1)
                    ->boolean(),
                IconEntry::make('step_2_active')
                    ->label('Paso 2 activo')
                    ->state(fn (Prospect $record): bool => ! $record->disable_step_2)
                    ->boolean(),
            ])->columns(2),
            Section::make('Correos enviados')->schema([
                RepeatableEntry::make('emails')
                    ->label('')
                    ->schema([
                        TextEntry::make('step')->label('Paso'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('personalized_by_ai')
                            ->label('Origen')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'IA' : 'Plantilla')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        TextEntry::make('rendered_subject')->label('Asunto enviado')->placeholder('—'),
                        TextEntry::make('scheduled_at')->dateTime()->label('Programado'),
                        TextEntry::make('sent_at')->dateTime()->label('Enviado'),
                        TextEntry::make('opened_at')->dateTime()->label('Abierto'),
                        TextEntry::make('clicked_at')->dateTime()->label('Clic'),
                        TextEntry::make('rendered_body_html')
                            ->label('Cuerpo enviado')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(3),
            ]),
        ]);
    }
}
