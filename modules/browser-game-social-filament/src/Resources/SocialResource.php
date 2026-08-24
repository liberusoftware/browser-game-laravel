<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\Social\Models\SocialRecord;

final class SocialResource extends Resource
{
    protected static ?string $model = SocialRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('kind')->required(), TextInput::make('status')->required(), TextInput::make('owner_id'), TextInput::make('team_id'), KeyValue::make('data')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')]);
    }

    public static function getPages(): array
    {
        return ['index' => Resources\SocialResource\Pages\ListSocial::route('/'), 'create' => Resources\SocialResource\Pages\CreateSocial::route('/create'), 'edit' => Resources\SocialResource\Pages\EditSocial::route('/{record}/edit')];
    }
}
