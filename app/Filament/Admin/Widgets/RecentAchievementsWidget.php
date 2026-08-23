<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAchievementsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Achievement Unlocks';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \DB::table('player_achievements')
                    ->join('players', 'player_achievements.player_id', '=', 'players.id')
                    ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
                    ->whereNotNull('player_achievements.unlocked_at')
                    ->select(
                        'players.username as player_username',
                        'achievements.name as achievement_name',
                        'achievements.points',
                        'player_achievements.unlocked_at'
                    )
                    ->orderBy('player_achievements.unlocked_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('player_username')
                    ->label('Player')
                    ->searchable(),
                TextColumn::make('achievement_name')
                    ->label('Achievement')
                    ->searchable(),
                TextColumn::make('points')
                    ->label('Points')
                    ->badge()
                    ->color('success'),
                TextColumn::make('unlocked_at')
                    ->label('Unlocked At')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
