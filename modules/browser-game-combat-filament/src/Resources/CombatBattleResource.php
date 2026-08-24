<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\Combat\Models\CombatBattle;

final class CombatBattleResource extends Resource
{
    protected static ?string $model = CombatBattle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('actor_id')->required(), TextInput::make('opponent_id')->required(), TextInput::make('status')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('actor_id'), TextColumn::make('opponent_id'), TextColumn::make('status')->badge(), TextColumn::make('turn')]);
    }

    public static function getPages(): array
    {
        return [];
    }
}
