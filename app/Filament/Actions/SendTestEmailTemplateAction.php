<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Actions\Prospecting\SendEmailTemplateTestAction;
use App\Models\EmailTemplate;
use Filament\Actions\Action as PageAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\Facades\Auth;

final class SendTestEmailTemplateAction
{
    public static function makeTableAction(): TableAction
    {
        return TableAction::make('sendTest')
            ->label('Enviar prueba')
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->form(self::formSchema())
            ->action(fn (EmailTemplate $record, array $data) => self::handle($record, $data));
    }

    public static function makePageAction(): PageAction
    {
        return PageAction::make('sendTest')
            ->label('Enviar prueba')
            ->icon('heroicon-o-paper-airplane')
            ->color('gray')
            ->form(self::formSchema());
    }

    /** @return array<int, Forms\Components\Component> */
    private static function formSchema(): array
    {
        return [
            Forms\Components\TextInput::make('email')
                ->label('Correo destino')
                ->email()
                ->required()
                ->maxLength(255)
                ->default(fn (): ?string => Auth::user()?->email),
            Forms\Components\Placeholder::make('preview_note')
                ->label('Nota')
                ->content('Se enviará con datos de ejemplo: "Empresa de Ejemplo S.A." y categoría "Logística". El asunto incluirá el prefijo [PRUEBA].'),
        ];
    }

    /** @param array{email: string} $data */
    public static function handle(EmailTemplate $record, array $data): void
    {
        app(SendEmailTemplateTestAction::class)->execute($record, $data['email']);

        Notification::make()
            ->title('Correo de prueba enviado')
            ->body('Revisa la bandeja de ' . $data['email'])
            ->success()
            ->send();
    }
}
