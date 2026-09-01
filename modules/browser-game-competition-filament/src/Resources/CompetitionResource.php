<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionFilament\Resources;

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
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;
use Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource\Pages\CreateCompetition;
use Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource\Pages\EditCompetition;
use Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource\Pages\ListCompetitions;

final class CompetitionResource extends Resource
{
    protected static ?string $model = CompetitionRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), Select::make('kind')->options(['pvp' => 'PvP', 'matchmaking' => 'Matchmaking', 'season' => 'Season', 'leaderboard' => 'Leaderboard'])->required(), Select::make('status')->options(['open' => 'Open', 'active' => 'Active', 'closed' => 'Closed'])->required(), TextInput::make('team_id'), KeyValue::make('data')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('kind')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('season')->sortable(),
            TextColumn::make('starts_at')->dateTime()->sortable(),
            TextColumn::make('ends_at')->dateTime()->sortable(),
            TextColumn::make('team_id'),
        ])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCompetitions::route('/'), 'create' => CreateCompetition::route('/create'), 'edit' => EditCompetition::route('/{record}/edit')];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
