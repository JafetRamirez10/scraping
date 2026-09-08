<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProspectEmailStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Bounced = 'bounced';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Sent => 'Enviado',
            self::Failed => 'Fallido',
            self::Skipped => 'Omitido',
            self::Bounced => 'Rebotado',
        };
    }
}
