<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Player;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LeaderboardWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Players Leaderboard';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Player::query()
                    ->whereNotNull('rank')
                    ->orderBy('rank')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 1 => 'success',
                        $state <= 3 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => '#'.$state),
                TextColumn::make('username')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('level')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => 'Lvl '.$state),
                TextColumn::make('experience')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => number_format($state).' XP'),
                TextColumn::make('score')
                    ->label('Total Score')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('last_rank_update')
                    ->dateTime()
                    ->label('Last Updated')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn (Player $record): string => Filament::getTenant()
                        ? route('filament.admin.resources.players.view', ['tenant' => Filament::getTenant(), 'record' => $record])
                        : '#')
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
