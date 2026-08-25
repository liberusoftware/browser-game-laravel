<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Items\Models\ItemsRecord;

final class ItemsResource extends Resource
{
    protected static ?string $model = ItemsRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('description')->maxLength(1000),
            TextInput::make('type')->required()->default('misc'),
            TextInput::make('rarity')->required()->default('common'),
            TextInput::make('slot'),
            TextInput::make('strength_bonus')->numeric()->default(0),
            TextInput::make('defense_bonus')->numeric()->default(0),
            TextInput::make('agility_bonus')->numeric()->default(0),
            TextInput::make('intelligence_bonus')->numeric()->default(0),
            TextInput::make('health_bonus')->numeric()->default(0),
            TextInput::make('mana_bonus')->numeric()->default(0),
            TextInput::make('max_durability')->numeric(),
            TextInput::make('max_stack')->numeric()->minValue(1),
            TextInput::make('min_level')->numeric()->required()->default(1),
            TextInput::make('sell_price')->numeric()->required()->default(0),
            TextInput::make('buy_price')->numeric()->required()->default(0),
            TextInput::make('status')->required()->default('active'),
            TextInput::make('team_id'),
            KeyValue::make('data'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
