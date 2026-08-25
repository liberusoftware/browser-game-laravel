<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\Combat\Models\CombatDefinition;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource\Pages\CreateCombatDefinition;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource\Pages\EditCombatDefinition;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource\Pages\ListCombatDefinitions;

final class CombatDefinitionResource extends Resource
{
    protected static ?string $model = CombatDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kind')->required()->options(['ability' => 'Ability', 'effect' => 'Effect', 'enemy' => 'Enemy', 'boss' => 'Boss', 'loot' => 'Loot']),
            TextInput::make('slug')->required()->maxLength(120),
            TextInput::make('name')->required()->maxLength(255),
            Textarea::make('effects')->json()->columnSpanFull(),
            Textarea::make('data')->json()->columnSpanFull(),
            TextInput::make('cooldown')->numeric()->minValue(0)->required(),
            Select::make('status')->required()->options(['active' => 'Active', 'disabled' => 'Disabled']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('kind')->badge()->sortable(),
            TextColumn::make('slug')->searchable()->sortable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('cooldown'),
            TextColumn::make('status')->badge(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([EditAction::make(), DeleteAction::make()])->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListCombatDefinitions::route('/'), 'create' => CreateCombatDefinition::route('/create'), 'edit' => EditCombatDefinition::route('/{record}/edit')];
    }
}
