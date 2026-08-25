<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldFilament\Resources;

use Filament\Actions\Action;
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
use Liberu\BrowserGame\World\Support\WorldManager;
use Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource\Pages\CreateWorldEntity;
use Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource\Pages\EditWorldEntity;
use Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource\Pages\ListWorldEntities;

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
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('kind')->badge(), TextColumn::make('status')->badge()])->actions([
            EditAction::make(),
            Action::make('grantUnlock')->label('Grant unlock')->requiresConfirmation()->visible(fn (WorldEntity $record): bool => filled($record->unlock_key))->action(function (WorldEntity $record): void {
                $user = auth()->user();
                $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
                abort_unless($team !== null, 403);
                app(WorldManager::class)->grantUnlock((string) auth()->id(), $record, $team->getAttribute('tenant_id'), (string) $team->getKey(), 'filament:unlock:'.auth()->id().':'.$record->getKey());
            }),
            DeleteAction::make(),
        ]);
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
        return ['index' => ListWorldEntities::route('/'), 'create' => CreateWorldEntity::route('/create'), 'edit' => EditWorldEntity::route('/{record}/edit')];
    }
}
