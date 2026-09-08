<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProspectStatus: string implements HasLabel
{
    case Discovered = 'discovered';
    case InSequence = 'in_sequence';
    case SequenceCompleted = 'sequence_completed';
    case NoEngagement = 'no_engagement';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';
    case Invalid = 'invalid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Discovered => 'Descubierto',
            self::InSequence => 'En secuencia',
            self::SequenceCompleted => 'Secuencia completada',
            self::NoEngagement => 'Sin interacción',
            self::Unsubscribed => 'Dado de baja',
            self::Bounced => 'Rebotado',
            self::Invalid => 'Inválido',
        };
    }
}
