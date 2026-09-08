<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailSource: string implements HasLabel
{
    case ContactPage = 'contact_page';
    case Snippet = 'snippet';
    case Website = 'website';
    case Manual = 'manual';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ContactPage => 'Página de contacto',
            self::Snippet => 'Resultado de búsqueda',
            self::Website => 'Sitio web',
            self::Manual => 'Manual',
        };
    }
}
