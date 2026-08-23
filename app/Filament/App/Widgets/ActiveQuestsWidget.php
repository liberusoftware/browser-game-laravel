<?php

namespace App\Filament\App\Widgets;

use App\Models\Player;
use App\Models\Quest;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActiveQuestsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quest::query()
                    ->whereHas('players', function (Builder $query) {
                        $user = Auth::user();
                        // $player = Player::where('user_id', $user->id)->first();
                        // if ($player) {
                        //     $query->where('player_id', $player->id);
                        // }
                    })
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Quest')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->extraAttributes([
                        'class' => 'quest-title',
                    ]),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->extraAttributes([
                        'class' => 'quest-description',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('reward_gold')
                    ->label('Reward')
                    ->numeric()
                    ->suffix(' gold')
                    ->icon('heroicon-m-currency-dollar')
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Quest $record): string => route('filament.app.resources.quests.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->heading('Active Quests')
            ->description('Track your ongoing adventures')
            ->emptyStateHeading('No active quests')
            ->emptyStateDescription('Visit the quest board to start new adventures!')
            ->emptyStateIcon('heroicon-o-map')
            ->defaultPaginationPageOption(5)
            ->extremePaginationLinks()
            ->striped();
    }
}
