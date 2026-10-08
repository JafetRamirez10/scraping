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
                ->modalDescription('Aplica solo a este prospecto. Un paso ya enviado no se cancela.')
                ->modalSubmitActionLabel('Guardar')
                ->fillForm(fn (): array => [
                    'disable_step_1' => (bool) $this->getRecord()->disable_step_1,
                    'disable_step_2' => (bool) $this->getRecord()->disable_step_2,
                ])
                ->form([
                    Forms\Components\Toggle::make('disable_step_1')
                        ->label('Desactivar paso 1')
                        ->helperText('Si está pendiente, no se envía.'),
                    Forms\Components\Toggle::make('disable_step_2')
                        ->label('Desactivar paso 2')
                        ->helperText('Si está pendiente, no se envía. Si aún no existe, no se programa.'),
                ])
                ->action(function (array $data): void {
                    /** @var Prospect $prospect */
                    $prospect = $this->getRecord();

                    app(SetProspectEmailStepAction::class)->sync(
                        $prospect,
                        (bool) ($data['disable_step_1'] ?? false),
                        (bool) ($data['disable_step_2'] ?? false),
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
                IconEntry::make('disable_step_1')->label('Paso 1 desactivado')->boolean(),
                IconEntry::make('disable_step_2')->label('Paso 2 desactivado')->boolean(),
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
