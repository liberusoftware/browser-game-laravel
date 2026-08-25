<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PlayerStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $user = Auth::user();
        $player = $user?->player;

        if (! $player) {
            return [
                Stat::make('Welcome!', 'Create your player')
                    ->description('Start your adventure')
                    ->descriptionIcon('heroicon-m-user-plus')
                    ->color('info'),
            ];
        }

        return [
            Stat::make('Level', $player->level ?? 1)
                ->description('Experience points: '.($player->experience ?? 0))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->extraAttributes([
                    'class' => 'game-stat-card',
                ]),

            Stat::make('Health', ($player->current_health ?? 100).'/'.($player->max_health ?? 100))
                ->description('Restore health to continue')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger')
                ->chart([65, 70, 75, 80, 85, 90, 95, 100])
                ->extraAttributes([
                    'class' => 'game-stat-card',
                ]),

            Stat::make('Gold', number_format($player->gold ?? 0))
                ->description('In-game currency')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'game-stat-card',
                ]),

            Stat::make('Quests', $player->quests()->count().' active')
                ->description('Complete quests for rewards')
                ->descriptionIcon('heroicon-m-map')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'game-stat-card',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        // 2 columns on mobile, 4 on desktop
        return 2;
    }
}
