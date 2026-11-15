<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ProspectOutcome;
use App\Models\Prospect;
use Filament\Widgets\ChartWidget;

class ProspectOutcomeChart extends ChartWidget
{
    protected static ?string $heading = 'Seguimiento de prospectos';

    protected static ?string $description = 'Responde, reunión, contrata y no interesa.';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Prospect::query()
            ->selectRaw('outcome, COUNT(*) as total')
            ->groupBy('outcome')
            ->pluck('total', 'outcome');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (ProspectOutcome::cases() as $case) {
            $labels[] = $case->getLabel();
            $data[] = (int) ($counts[$case->value] ?? 0);
            $colors[] = match ($case) {
                ProspectOutcome::Replied => '#2563eb',
                ProspectOutcome::MeetingRequested => '#d97706',
                ProspectOutcome::MeetingHeld => '#059669',
                ProspectOutcome::Hired => '#047857',
                ProspectOutcome::NotInterested => '#dc2626',
            };
        }

        $labels[] = 'Sin seguimiento';
        $data[] = (int) ($counts[null] ?? $counts[''] ?? 0);
        $colors[] = '#94a3b8';

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }
}
