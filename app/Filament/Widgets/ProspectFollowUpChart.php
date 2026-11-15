<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ProspectOutcome;
use App\Models\Prospect;
use Filament\Widgets\ChartWidget;

class ProspectFollowUpChart extends ChartWidget
{
    protected static ?string $heading = 'Correos según seguimiento';

    protected static ?string $description = 'Contrata, no interesa y reunión realizada ya no reciben correos.';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $stopped = Prospect::query()
            ->whereIn('outcome', [
                ProspectOutcome::MeetingHeld->value,
                ProspectOutcome::Hired->value,
                ProspectOutcome::NotInterested->value,
            ])
            ->count();

        $continuing = Prospect::query()->count() - $stopped;

        return [
            'labels' => ['Pueden recibir correos', 'Correos detenidos'],
            'datasets' => [
                [
                    'label' => 'Prospectos',
                    'data' => [$continuing, $stopped],
                    'backgroundColor' => ['#2563eb', '#dc2626'],
                ],
            ],
        ];
    }
}
