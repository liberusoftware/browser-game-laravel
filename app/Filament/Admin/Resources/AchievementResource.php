<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AchievementResource\Pages;
use App\Models\Achievement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Management';

    protected static ?string $navigationLabel = 'Achievements';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('icon')
                    ->maxLength(255)
                    ->placeholder('e.g., heroicon-o-trophy'),
                TextInput::make('points')
                    ->numeric()
                    ->default(10)
                    ->minValue(1)
                    ->maxValue(1000),
                Select::make('requirement_type')
                    ->options([
                        'level' => 'Level',
                        'quests_completed' => 'Quests Completed',
                        'items_collected' => 'Items Collected',
                        'experience' => 'Experience',
                    ])
                    ->required(),
                TextInput::make('requirement_value')
                    ->numeric()
                    ->required()
                    ->minValue(1),
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
                    ->searchable(),
                TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('requirement_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('requirement_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => Pages\ListAchievements::route('/'),
            'create' => Pages\CreateAchievement::route('/create'),
            'view' => Pages\ViewAchievement::route('/{record}'),
            'edit' => Pages\EditAchievement::route('/{record}/edit'),
        ];
    }
}
