<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Pages\AccountSetup;
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
                    ->description('Finish account setup to prepare your character')
                    ->url(AccountSetup::getUrl(panel: 'app'))
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

            Stat::make('Health', ($player->health ?? 100).'/'.($player->max_health ?? 100))
                ->description(($player->health ?? 100) < ($player->max_health ?? 100) ? 'Recover before your next battle' : 'Ready for your next adventure')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'game-stat-card',
                ]),

            Stat::make('Experience', number_format($player->experience ?? 0))
                ->description('Earned through your adventures')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'game-stat-card',
                ]),

            Stat::make('Quests', $player->activeQuests()->count().' active')
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
