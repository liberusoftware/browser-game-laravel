<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\GameCore\Models\GameClock;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource\Pages\CreateGameClock;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource\Pages\EditGameClock;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource\Pages\ListGameClocks;

final class GameClockResource extends Resource
{
    protected static ?string $model = GameClock::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('world_id')->required()->options(fn (): array => GameWorldResource::getEloquentQuery()->pluck('name', 'id')->all())->searchable(),
            DateTimePicker::make('current_at')->required()->seconds(false),
            TextInput::make('speed')->required()->numeric()->minValue(0),
            Toggle::make('paused'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('world_id')->label('World')->searchable(),
            TextColumn::make('current_at')->dateTime()->sortable(),
            TextColumn::make('speed'),
            IconColumn::make('paused')->boolean(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([EditAction::make(), DeleteAction::make()])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('world_id', GameWorldResource::getEloquentQuery()->select('id'));
    }

    public static function getPages(): array
    {
        return ['index' => ListGameClocks::route('/'), 'create' => CreateGameClock::route('/create'), 'edit' => EditGameClock::route('/{record}/edit')];
    }
}
