<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Player;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPlayersTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Players';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Player::query()->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('experience')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Joined'),
            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn (Player $record): string => route('filament.admin.resources.players.view', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
