<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\DailyEmailStat;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ProspectingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayStats = DailyEmailStat::query()->whereDate('date', today())->first();
        $prospectsWeek = Prospect::query()->where('created_at', '>=', now()->subDays(7))->count();
        $serpCredits = Cache::get('serpapi_credits_remaining', '—');

        return [
            Stat::make('Prospectos (7 días)', (string) $prospectsWeek),
            Stat::make('Correos hoy', (string) ($todayStats->sent_count ?? 0)),
            Stat::make('Bounces hoy', (string) ($todayStats->bounced_count ?? 0)),
            Stat::make('Créditos SerpAPI', (string) $serpCredits),
        ];
    }
}
