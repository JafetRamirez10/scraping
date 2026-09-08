<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Actions\SendTestEmailTemplateAction;
use App\Filament\Resources\EmailTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendTestEmailTemplateAction::makePageAction()
                ->action(fn (array $data) => SendTestEmailTemplateAction::handle($this->record, $data)),
            Actions\DeleteAction::make(),
        ];
    }
}
