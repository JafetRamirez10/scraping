<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AiSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ManageAiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.manage-ai-settings';

    protected static ?string $navigationGroup = 'Prospección';

    protected static ?string $navigationLabel = 'IA / DeepSeek';

    protected static ?string $title = 'Personalización con DeepSeek';

    protected static ?int $navigationSort = 50;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = AiSetting::current();

        $this->form->fill([
            'deepseek_enabled' => $settings->deepseek_enabled,
            'deepseek_model' => $settings->deepseek_model,
            'daily_limit' => $settings->daily_limit,
            'system_prompt' => $settings->system_prompt,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('DeepSeek')
                    ->description('Personaliza asuntos y cuerpos usando la plantilla + datos de SerpAPI.')
                    ->schema([
                        Forms\Components\Toggle::make('deepseek_enabled')
                            ->label('Activar personalización con DeepSeek')
                            ->helperText('Si falla la IA, se envía la plantilla normal.')
                            ->inline(false),
                        Forms\Components\Placeholder::make('api_key_status')
                            ->label('API key')
                            ->content(fn (): string => filled(config('services.deepseek.key'))
                                ? 'Configurada en .env (DEEPSEEK_API_KEY)'
                                : 'No configurada. Agrega DEEPSEEK_API_KEY en .env'),
                        Forms\Components\Placeholder::make('usage_today')
                            ->label('Uso hoy')
                            ->content(fn (): string => ((int) Cache::get('deepseek_personalizations_'.now()->format('Y-m-d'), 0)).' personalizaciones'),
                        Forms\Components\TextInput::make('deepseek_model')
                            ->label('Modelo')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Ej. deepseek-chat o deepseek-v4-flash'),
                        Forms\Components\TextInput::make('daily_limit')
                            ->label('Límite diario')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->required(),
                        Forms\Components\Textarea::make('system_prompt')
                            ->label('Prompt de sistema (opcional)')
                            ->rows(8)
                            ->helperText('Déjalo vacío para usar el prompt por defecto.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = AiSetting::current();

        $settings->update([
            'deepseek_enabled' => (bool) ($state['deepseek_enabled'] ?? false),
            'deepseek_model' => (string) ($state['deepseek_model'] ?? 'deepseek-chat'),
            'daily_limit' => (int) ($state['daily_limit'] ?? 200),
            'system_prompt' => filled($state['system_prompt'] ?? null)
                ? (string) $state['system_prompt']
                : null,
        ]);

        Notification::make()
            ->title('Configuración de IA guardada')
            ->success()
            ->send();
    }
}
