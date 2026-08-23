<?php

namespace App\Filament\Admin\Resources\PlayerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50),
                TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pivot.progress')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('pivot.unlocked_at')
                    ->label('Unlocked At')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state): string => $state ? $state->format('M d, Y') : 'Locked'),
            ])
            ->filters([
                Tables\Filters\Filter::make('unlocked')
                    ->query(fn ($query) => $query->whereNotNull('player_achievements.unlocked_at'))
                    ->label('Unlocked Only'),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
