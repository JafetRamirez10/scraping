<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Actions\SendTestEmailTemplateAction;
use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Prospección';

    protected static ?string $modelLabel = 'Plantilla';

    protected static ?string $pluralModelLabel = 'Plantillas de correo';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(150),
            Forms\Components\Select::make('step')
                ->label('Paso')
                ->options([1 => '1', 2 => '2', 3 => '3'])
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('subject')
                ->label('Asunto')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('body_html')
                ->label('Cuerpo HTML')
                ->required()
                ->rows(10)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('body_text')
                ->label('Cuerpo texto')
                ->rows(6)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')
                ->label('Activa')
                ->default(true),
            Forms\Components\Placeholder::make('ai_note')
                ->label('Personalización IA')
                ->content('Si DeepSeek está activo en Prospección → IA / DeepSeek, esta plantilla se usa como base y el texto final puede variar por prospecto.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('step')->label('Paso')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre'),
                Tables\Columns\TextColumn::make('subject')->label('Asunto')->limit(40),
                Tables\Columns\IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->actions([
                SendTestEmailTemplateAction::makeTableAction(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
