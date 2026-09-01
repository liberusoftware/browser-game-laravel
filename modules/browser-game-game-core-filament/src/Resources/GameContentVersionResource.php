<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources;

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
use Liberu\BrowserGame\GameCore\Models\GameContentVersion;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameContentVersionResource\Pages\CreateGameContentVersion;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameContentVersionResource\Pages\EditGameContentVersion;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameContentVersionResource\Pages\ListGameContentVersions;

final class GameContentVersionResource extends Resource
{
    protected static ?string $model = GameContentVersion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('world_id')->required()->options(fn (): array => GameWorldResource::getEloquentQuery()->pluck('name', 'id')->all())->searchable(),
            TextInput::make('version')->required()->numeric()->minValue(1),
            Select::make('status')->required()->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']),
            TextInput::make('content_hash')->required()->maxLength(128),
            KeyValue::make('manifest')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('world_id')->label('World'), TextColumn::make('version')->sortable(), TextColumn::make('content_hash')->limit(24), TextColumn::make('status')->badge(), TextColumn::make('published_at')->dateTime(), TextColumn::make('updated_at')->dateTime()->sortable()])->actions([EditAction::make(), DeleteAction::make()])->defaultSort('version', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('world_id', GameWorldResource::getEloquentQuery()->select('id'));
    }

    public static function getPages(): array
    {
        return ['index' => ListGameContentVersions::route('/'), 'create' => CreateGameContentVersion::route('/create'), 'edit' => EditGameContentVersion::route('/{record}/edit')];
    }
}
