<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlayerResource\Pages\CreatePlayer;
use App\Filament\Admin\Resources\PlayerResource\Pages\EditPlayer;
use App\Filament\Admin\Resources\PlayerResource\Pages\ListPlayers;
use App\Filament\Admin\Resources\PlayerResource\Pages\ViewPlayer;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\QuestsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ResourcesRelationManager;
use App\Models\Player;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Players';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';

    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'email', 'level'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->username;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Email' => $record->email,
            'Level' => $record->level,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->description('Basic player account details')
                    ->schema([
                        TextInput::make('username')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique username for the player'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Player\'s email address'),
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                            ->helperText('Leave blank to keep current password'),
                    ])
                    ->columns(2),

                Section::make('Game Stats')
                    ->description('Player game progression and statistics')
                    ->schema([
                        TextInput::make('level')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Current player level (1-100)'),
                        TextInput::make('experience')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Total experience points'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 1 => 'success',
                        $state <= 10 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ? '#'.$state : 'Unranked'),
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
                TextColumn::make('score')
                    ->label('Total Score')
                    ->numeric()
                    ->sortable()
                    ->default(0),
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
                Filter::make('high_level')
                    ->query(fn (Builder $query): Builder => $query->where('level', '>=', 10))
                    ->label('High Level Players (10+)'),
                Filter::make('top_ranked')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('rank')->where('rank', '<=', 100))
                    ->label('Top 100 Ranked'),
                SelectFilter::make('level_range')
                    ->label('Level Range')
                    ->options([
                        '1-10' => 'Beginner (1-10)',
                        '11-25' => 'Intermediate (11-25)',
                        '26-50' => 'Advanced (26-50)',
                        '51-100' => 'Expert (51-100)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            '1-10' => $query->whereBetween('level', [1, 10]),
                            '11-25' => $query->whereBetween('level', [11, 25]),
                            '26-50' => $query->whereBetween('level', [26, 50]),
                            '51-100' => $query->whereBetween('level', [51, 100]),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('adjustLevel')
                        ->label('Adjust Level')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->form([
                            TextInput::make('level_change')
                                ->label('Level Adjustment')
                                ->helperText('Use positive numbers to increase, negative to decrease')
                                ->numeric()
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each(function ($record) use ($data) {
                                $newLevel = max(1, min(100, $record->level + $data['level_change']));
                                $record->update(['level' => $newLevel]);
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('rank', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            QuestsRelationManager::class,
            RelationManagers\AchievementsRelationManager::class,
            ItemsRelationManager::class,
            QuestsRelationManager::class,
            ResourcesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlayers::route('/'),
            'create' => CreatePlayer::route('/create'),
            'view' => ViewPlayer::route('/{record}'),
            'edit' => EditPlayer::route('/{record}/edit'),
        ];
    }
}
