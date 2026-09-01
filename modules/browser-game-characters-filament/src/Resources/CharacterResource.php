<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource\Pages\CreateCharacter;
use Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource\Pages\EditCharacter;
use Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource\Pages\ListCharacters;

final class CharacterResource extends Resource
{
    protected static ?string $model = GameCharacter::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('race')->required()->maxLength(80),
            TextInput::make('class')->required()->maxLength(80),
            TextInput::make('player_id')->required()->maxLength(120),
            TextInput::make('background')->maxLength(120),
            TextInput::make('level')->numeric()->disabled(),
            TextInput::make('experience')->numeric()->disabled(),
            TextInput::make('health')->numeric()->disabled(),
            TextInput::make('max_health')->numeric()->disabled(),
            TextInput::make('mana')->numeric()->disabled(),
            TextInput::make('max_mana')->numeric()->disabled(),
            TextInput::make('strength')->numeric()->disabled(),
            TextInput::make('defense')->numeric()->disabled(),
            TextInput::make('agility')->numeric()->disabled(),
            TextInput::make('intelligence')->numeric()->disabled(),
            TextInput::make('stat_points')->numeric()->disabled(),
            TextInput::make('available_skill_points')->numeric()->disabled(),
            TextInput::make('respec_count')->numeric()->disabled(),
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
            TextColumn::make('health')->numeric()->label('Health'),
            TextColumn::make('available_skill_points')->numeric()->label('Skill points'),
        ])->actions([EditAction::make(), DeleteAction::make()])->defaultSort('level', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharacters::route('/'),
            'create' => CreateCharacter::route('/create'),
            'edit' => EditCharacter::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
