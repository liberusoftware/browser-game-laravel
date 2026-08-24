<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\GameCore\Models\GameWorld;

final class GameWorldResource extends Resource
{
    protected static ?string $model = GameWorld::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('slug')->required()->alphaDash()->maxLength(120),
            TextInput::make('status')->required()->datalist(['draft', 'active', 'archived']),
            Textarea::make('metadata')->json()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('slug')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Resources\GameWorldResource\Pages\ListGameWorlds::route('/'), 'create' => Resources\GameWorldResource\Pages\CreateGameWorld::route('/create'), 'edit' => Resources\GameWorldResource\Pages\EditGameWorld::route('/{record}/edit')];
    }
}
