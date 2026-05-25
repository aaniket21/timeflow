<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', \App\Models\User::count()),
            Stat::make('Total Sessions', \App\Models\TimeSession::count()),
            Stat::make('Total Challenges', \App\Models\DailyChallenge::count()),
        ];
    }
}
