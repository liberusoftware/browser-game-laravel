<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsFilament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\Quests\Models\Quest;

final class QuestResource extends Resource
{
    protected static ?string $model = Quest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('slug')->required(), TextInput::make('storyline'), TextInput::make('status')->required(), Toggle::make('repeatable'), Textarea::make('objectives')->json()->required(), Textarea::make('prerequisites')->json(), Textarea::make('branches')->json(), Textarea::make('dialogue')->json(), Textarea::make('rewards')->json()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('slug'), TextColumn::make('status')->badge()]);
    }

    public static function getPages(): array
    {
        return ['index' => Resources\QuestResource\Pages\ListQuests::route('/'), 'create' => Resources\QuestResource\Pages\CreateQuest::route('/create'), 'edit' => Resources\QuestResource\Pages\EditQuest::route('/{record}/edit')];
    }
}
