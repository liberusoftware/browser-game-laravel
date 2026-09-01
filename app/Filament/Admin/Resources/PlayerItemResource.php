<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlayerItemResource\Pages\CreatePlayerItem;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\EditPlayerItem;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\ListPlayerItems;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\ViewPlayerItem;
use App\Models\Player_Item;
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
use Illuminate\Database\Eloquent\Builder;

class PlayerItemResource extends Resource
{
    protected static ?string $model = Player_Item::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Player Items';

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
                Select::make('item_id')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Item'),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['player', 'item']))
            ->columns([
                TextColumn::make('player.username')
                    ->searchable()
                    ->sortable()
                    ->label('Player'),
                TextColumn::make('item.name')
                    ->searchable()
                    ->sortable()
                    ->label('Item'),
                TextColumn::make('item.type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'weapon' => 'danger',
                        'armor' => 'info',
                        'consumable' => 'success',
                        'material' => 'warning',
                        'quest' => 'primary',
                        default => 'gray',
                    })
                    ->label('Item Type'),
                TextColumn::make('quantity')
                    ->numeric()
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
                SelectFilter::make('player')
                    ->relationship('player', 'username')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('item_type')
                    ->options([
                        'weapon' => 'Weapon',
                        'armor' => 'Armor',
                        'consumable' => 'Consumable',
                        'material' => 'Material',
                        'quest' => 'Quest Item',
                        'misc' => 'Miscellaneous',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereHas('item', fn (Builder $query) => $query->where('type', $value))
                        );
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
            'index' => ListPlayerItems::route('/'),
            'create' => CreatePlayerItem::route('/create'),
            'view' => ViewPlayerItem::route('/{record}'),
            'edit' => EditPlayerItem::route('/{record}/edit'),
        ];
    }
}
