<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Combat\Models\CombatBattle;
use Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource\Pages\CreateCombatBattle;
use Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource\Pages\EditCombatBattle;
use Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource\Pages\ListCombatBattles;

final class CombatBattleResource extends Resource
{
    protected static ?string $model = CombatBattle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'Live Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('actor_id')->required(),
            TextInput::make('opponent_id')->required(),
            Select::make('status')->required()->options(['active' => 'Active', 'completed' => 'Completed', 'abandoned' => 'Abandoned']),
            KeyValue::make('state'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('actor_id'), TextColumn::make('opponent_id'), TextColumn::make('status')->badge(), TextColumn::make('turn')])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCombatBattles::route('/'), 'create' => CreateCombatBattle::route('/create'), 'edit' => EditCombatBattle::route('/{record}/edit')];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
