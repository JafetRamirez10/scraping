<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProspect extends ViewRecord
{
    protected static string $resource = ProspectResource::class;

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
