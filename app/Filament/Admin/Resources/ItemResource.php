<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ItemResource\Pages\CreateItem;
use App\Filament\Admin\Resources\ItemResource\Pages\EditItem;
use App\Filament\Admin\Resources\ItemResource\Pages\ListItems;
use App\Filament\Admin\Resources\ItemResource\Pages\ViewItem;
use App\Models\Item;
use Filament\Actions\BulkAction;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Content';

    protected static ?string $navigationLabel = 'Items';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'type', 'rarity'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Type' => ucfirst($record->type),
            'Rarity' => ucfirst($record->rarity),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Details')
                    ->description('Basic item information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The name of the item'),
                        Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(4)
                            ->helperText('A description of the item and its effects'),
                    ]),

                Section::make('Item Properties')
                    ->description('Configure item type and rarity')
                    ->schema([
                        Select::make('type')
                            ->options([
                                'weapon' => 'Weapon',
                                'armor' => 'Armor',
                                'consumable' => 'Consumable',
                                'material' => 'Material',
                                'quest' => 'Quest Item',
                                'misc' => 'Miscellaneous',
                            ])
                            ->required()
                            ->helperText('The category of this item'),
                        Select::make('rarity')
                            ->options([
                                'common' => 'Common',
                                'uncommon' => 'Uncommon',
                                'rare' => 'Rare',
                                'epic' => 'Epic',
                                'legendary' => 'Legendary',
                            ])
                            ->required()
                            ->helperText('How rare this item is'),
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
                TextColumn::make('type')
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
                TextColumn::make('rarity')
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
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),
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
                SelectFilter::make('type')
                    ->options([
                        'weapon' => 'Weapon',
                        'armor' => 'Armor',
                        'consumable' => 'Consumable',
                        'material' => 'Material',
                        'quest' => 'Quest Item',
                        'misc' => 'Miscellaneous',
                    ]),
                SelectFilter::make('rarity')
                    ->options([
                        'common' => 'Common',
                        'uncommon' => 'Uncommon',
                        'rare' => 'Rare',
                        'epic' => 'Epic',
                        'legendary' => 'Legendary',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ReplicateAction::make()
                    ->excludeAttributes(['created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (Item $replica): void {
                        $replica->name = $replica->name.' (Copy)';
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('updateRarity')
                        ->label('Update Rarity')
                        ->icon('heroicon-o-star')
                        ->form([
                            Select::make('rarity')
                                ->label('New Rarity')
                                ->options([
                                    'common' => 'Common',
                                    'uncommon' => 'Uncommon',
                                    'rare' => 'Rare',
                                    'epic' => 'Epic',
                                    'legendary' => 'Legendary',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['rarity' => $data['rarity']]);
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
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
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'view' => ViewItem::route('/{record}'),
            'edit' => EditItem::route('/{record}/edit'),
        ];
    }
}
