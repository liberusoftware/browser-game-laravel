<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\World\Models\WorldEntity;

final class WorldEntityResource extends Resource
{
    protected static ?string $model = WorldEntity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('slug')->required(), TextInput::make('kind')->required(), TextInput::make('status')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('kind')->badge(), TextColumn::make('status')->badge()]);
    }

    public static function getPages(): array
    {
        return ['index' => Resources\WorldEntityResource\Pages\ListWorldEntities::route('/'), 'create' => Resources\WorldEntityResource\Pages\CreateWorldEntity::route('/create'), 'edit' => Resources\WorldEntityResource\Pages\EditWorldEntity::route('/{record}/edit')];
    }
}
