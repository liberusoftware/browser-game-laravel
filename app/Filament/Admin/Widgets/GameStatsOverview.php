<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Guild;
use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Quest;
use App\Models\Quest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GameStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPlayers = Player::count();
        $recentPlayers = Player::where('created_at', '>=', now()->subDays(7))->count();
        $avgLevel = round(Player::avg('level') ?? 0, 1);
        $activeQuests = Player_Quest::where('status', 'in_progress')->count();

        return [
            Stat::make('Total Players', $totalPlayers)
                ->description($recentPlayers.' new this week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, $recentPlayers]),
            Stat::make('Average Level', $avgLevel)
                ->description('Player progression')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
            Stat::make('Active Guilds', Guild::count())
                ->description('Total guilds')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),
            Stat::make('Items Available', Item::count())
                ->description('Total items in game')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Quests Available', Quest::count())
                ->description('Total quests')
                ->descriptionIcon('heroicon-m-map')
                ->color('warning'),
            Stat::make('Active Quests', $activeQuests)
                ->description('Quests in progress')
                ->descriptionIcon('heroicon-m-flag')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
