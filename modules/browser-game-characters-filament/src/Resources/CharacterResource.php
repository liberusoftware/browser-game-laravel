<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\Characters\Models\GameCharacter;

final class CharacterResource extends Resource
{
    protected static ?string $model = GameCharacter::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('race')->required()->maxLength(80),
            TextInput::make('class')->required()->maxLength(80),
            TextInput::make('level')->numeric()->disabled(),
            TextInput::make('experience')->numeric()->disabled(),
            TextInput::make('health')->numeric()->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('race')->badge(),
            TextColumn::make('class')->badge(),
            TextColumn::make('level')->numeric()->sortable(),
            TextColumn::make('experience')->numeric()->sortable(),
        ])->defaultSort('level', 'desc');
    }

    public static function getPages(): array
    {
        return [];
    }
}
