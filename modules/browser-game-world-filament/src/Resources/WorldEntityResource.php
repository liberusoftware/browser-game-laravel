<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\World\Models\WorldEntity;

final class WorldEntityResource extends Resource
{
    protected static ?string $model = WorldEntity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        $kinds = config('browser-game.world.kinds', ['region', 'location', 'map', 'encounter', 'npc', 'resource', 'weather', 'unlock']);

        return $schema->components([TextInput::make('name')->required(), TextInput::make('slug')->required(), Select::make('kind')->options(array_combine($kinds, $kinds))->required(), Select::make('status')->options(['active' => 'Active', 'hidden' => 'Hidden', 'archived' => 'Archived'])->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('kind')->badge(), TextColumn::make('status')->badge()])->actions([EditAction::make(), DeleteAction::make()]);
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
        return ['index' => Resources\WorldEntityResource\Pages\ListWorldEntities::route('/'), 'create' => Resources\WorldEntityResource\Pages\CreateWorldEntity::route('/create'), 'edit' => Resources\WorldEntityResource\Pages\EditWorldEntity::route('/{record}/edit')];
    }
}
