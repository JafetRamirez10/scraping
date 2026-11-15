<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProspectOutcome: string implements HasColor, HasLabel
{
    case Replied = 'replied';
    case MeetingRequested = 'meeting_requested';
    case MeetingHeld = 'meeting_held';
    case Hired = 'hired';
    case NotInterested = 'not_interested';

    public function getLabel(): string
    {
        return match ($this) {
            self::Replied => 'Responde mensaje',
            self::MeetingRequested => 'Solicita reunión',
            self::MeetingHeld => 'Se realizó la reunión',
            self::Hired => 'Contrata',
            self::NotInterested => 'No interesa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Replied => 'info',
            self::MeetingRequested => 'warning',
            self::MeetingHeld => 'success',
            self::Hired => 'success',
            self::NotInterested => 'danger',
        };
    }

    public function stopsEmails(): bool
    {
        return match ($this) {
            self::MeetingHeld, self::Hired, self::NotInterested => true,
            self::Replied, self::MeetingRequested => false,
        };
    }
}
