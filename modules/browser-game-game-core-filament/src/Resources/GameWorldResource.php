<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\GameCore\Models\GameWorld;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource\Pages\CreateGameWorld;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource\Pages\EditGameWorld;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource\Pages\ListGameWorlds;

final class GameWorldResource extends Resource
{
    protected static ?string $model = GameWorld::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Operations';

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
        ])->actions([EditAction::make(), DeleteAction::make()])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()
            ->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }

    public static function getPages(): array
    {
        return ['index' => ListGameWorlds::route('/'), 'create' => CreateGameWorld::route('/create'), 'edit' => EditGameWorld::route('/{record}/edit')];
    }
}
