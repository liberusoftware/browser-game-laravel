<?php

namespace App\Filament\Admin\Resources\PlayerResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Player Inventory';

    protected static ?string $recordTitleAttribute = 'item.name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('item_id')
                    ->label('Item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.name')
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'weapon' => 'danger',
                        'armor' => 'info',
                        'consumable' => 'success',
                        'material' => 'warning',
                        'quest' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('item.rarity')
                    ->label('Rarity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'common' => 'gray',
                        'uncommon' => 'success',
                        'rare' => 'info',
                        'epic' => 'warning',
                        'legendary' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('item.type')
                    ->label('Item Type')
                    ->relationship('item', 'type')
                    ->options([
                        'weapon' => 'Weapon',
                        'armor' => 'Armor',
                        'consumable' => 'Consumable',
                        'material' => 'Material',
                        'quest' => 'Quest Item',
                        'misc' => 'Miscellaneous',
                    ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
