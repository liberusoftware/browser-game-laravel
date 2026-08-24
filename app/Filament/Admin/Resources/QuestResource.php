<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\QuestResource\Pages\CreateQuest;
use App\Filament\Admin\Resources\QuestResource\Pages\EditQuest;
use App\Filament\Admin\Resources\QuestResource\Pages\ListQuests;
use App\Filament\Admin\Resources\QuestResource\Pages\ViewQuest;
use App\Models\Quest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestResource extends Resource
{
    protected static ?string $model = Quest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Management';

    protected static ?string $navigationLabel = 'Quests';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'XP Reward' => $record->experience_reward,
            'Item Reward' => $record->itemReward->name ?? 'None',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quest Details')
                    ->description('Basic quest information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for the quest'),
                        Textarea::make('description')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4)
                            ->helperText('The quest story and objectives'),
                    ]),

                Section::make('Rewards')
                    ->description('Configure quest rewards')
                    ->schema([
                        TextInput::make('experience_reward')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label('Experience Reward')
                            ->helperText('XP awarded upon quest completion'),
                        Select::make('item_reward_id')
                            ->relationship('itemReward', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Item Reward')
                            ->placeholder('Select an item reward (optional)')
                            ->helperText('Item awarded upon quest completion'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),
                TextColumn::make('experience_reward')
                    ->numeric()
                    ->sortable()
                    ->label('XP Reward'),
                TextColumn::make('itemReward.name')
                    ->label('Item Reward')
                    ->placeholder('None')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('has_item_reward')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('item_reward_id'))
                    ->label('Has Item Reward'),
                Filter::make('high_xp')
                    ->query(fn (Builder $query): Builder => $query->where('experience_reward', '>=', 100))
                    ->label('High XP Reward (100+)'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ReplicateAction::make()
                    ->excludeAttributes(['created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (Quest $replica): void {
                        $replica->name = $replica->name.' (Copy)';
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuests::route('/'),
            'create' => CreateQuest::route('/create'),
            'view' => ViewQuest::route('/{record}'),
            'edit' => EditQuest::route('/{record}/edit'),
        ];
    }
}
