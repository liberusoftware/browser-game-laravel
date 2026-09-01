<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GameResourceResource\Pages\CreateGameResource;
use App\Filament\Admin\Resources\GameResourceResource\Pages\EditGameResource;
use App\Filament\Admin\Resources\GameResourceResource\Pages\ListGameResources;
use App\Filament\Admin\Resources\GameResourceResource\Pages\ViewGameResource;
use App\Models\Resource as GameResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GameResourceResource extends Resource
{
    protected static ?string $model = GameResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Content';

    protected static ?string $navigationLabel = 'Player Resources';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('player_id')
                    ->relationship('player', 'username')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Player'),
                Select::make('resource_type')
                    ->options([
                        'gold' => 'Gold',
                        'wood' => 'Wood',
                        'stone' => 'Stone',
                        'iron' => 'Iron',
                        'food' => 'Food',
                        'energy' => 'Energy',
                        'gems' => 'Gems',
                    ])
                    ->required()
                    ->label('Resource Type'),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.username')
                    ->searchable()
                    ->sortable()
                    ->label('Player'),
                TextColumn::make('resource_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'gold' => 'warning',
                        'wood' => 'success',
                        'stone' => 'gray',
                        'iron' => 'info',
                        'food' => 'primary',
                        'energy' => 'danger',
                        'gems' => 'purple',
                        default => 'gray',
                    })
                    ->sortable()
                    ->label('Resource Type'),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state)),
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
                SelectFilter::make('resource_type')
                    ->options([
                        'gold' => 'Gold',
                        'wood' => 'Wood',
                        'stone' => 'Stone',
                        'iron' => 'Iron',
                        'food' => 'Food',
                        'energy' => 'Energy',
                        'gems' => 'Gems',
                    ]),
                SelectFilter::make('player')
                    ->relationship('player', 'username')
                    ->searchable()
                    ->preload(),
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
            'index' => ListGameResources::route('/'),
            'create' => CreateGameResource::route('/create'),
            'view' => ViewGameResource::route('/{record}'),
            'edit' => EditGameResource::route('/{record}/edit'),
        ];
    }
}
