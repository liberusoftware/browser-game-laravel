<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\GameCore\Models\GameMaintenanceState;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource\Pages\CreateGameMaintenanceState;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource\Pages\EditGameMaintenanceState;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource\Pages\ListGameMaintenanceStates;

final class GameMaintenanceStateResource extends Resource
{
    protected static ?string $model = GameMaintenanceState::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('world_id')->required()->options(fn (): array => GameWorldResource::getEloquentQuery()->pluck('name', 'id')->all())->searchable(),
            Select::make('status')->required()->options(['scheduled' => 'Scheduled', 'active' => 'Active', 'resolved' => 'Resolved']),
            Textarea::make('message')->maxLength(2000)->columnSpanFull(),
            DateTimePicker::make('starts_at')->seconds(false),
            DateTimePicker::make('ends_at')->seconds(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('world_id')->label('World'), TextColumn::make('status')->badge(), TextColumn::make('starts_at')->dateTime(), TextColumn::make('ends_at')->dateTime(), TextColumn::make('updated_at')->dateTime()->sortable()])->actions([EditAction::make(), DeleteAction::make()])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('world_id', GameWorldResource::getEloquentQuery()->select('id'));
    }

    public static function getPages(): array
    {
        return ['index' => ListGameMaintenanceStates::route('/'), 'create' => CreateGameMaintenanceState::route('/create'), 'edit' => EditGameMaintenanceState::route('/{record}/edit')];
    }
}
