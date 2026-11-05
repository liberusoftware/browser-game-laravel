<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Pages\QuestJournal;
use App\Models\Quest;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ActiveQuestsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $playerId = auth()->user()?->player?->getKey();

        return $table
            ->query(Quest::query()->whereHas('players', function (Builder $query) use ($playerId): void {
                $query->whereKey($playerId)->where('player__quests.status', 'in-progress');
            })->with('itemReward'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Quest')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('description')->limit(80)->wrap(),
                Tables\Columns\TextColumn::make('experience_reward')->label('Experience')->numeric()->suffix(' XP')->color('warning'),
                Tables\Columns\TextColumn::make('itemReward.name')->label('Item reward')->placeholder('No item reward'),
            ])
            ->headerActions([
                Action::make('explore')->label('Explore quests')->icon('heroicon-o-map')->url(QuestJournal::getUrl(panel: 'app')),
            ])
            ->heading('Your active adventures')
            ->description('A clear objective for your next session')
            ->emptyStateHeading('Your next chapter is waiting')
            ->emptyStateDescription('Explore the quest journal to find your first adventure and preview its rewards.')
            ->emptyStateIcon('heroicon-o-map')
            ->defaultPaginationPageOption(5);
    }
}
