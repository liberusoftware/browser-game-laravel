<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Quests\Models\Quest;
use Liberu\BrowserGame\QuestsFilament\Resources\QuestResource\Pages\CreateQuest;
use Liberu\BrowserGame\QuestsFilament\Resources\QuestResource\Pages\EditQuest;
use Liberu\BrowserGame\QuestsFilament\Resources\QuestResource\Pages\ListQuests;

final class QuestResource extends Resource
{
    protected static ?string $model = Quest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('slug')->required(), TextInput::make('storyline'), TextInput::make('status')->required(), Toggle::make('repeatable'), Textarea::make('objectives')->json()->required(), Textarea::make('prerequisites')->json(), Textarea::make('branches')->json(), Textarea::make('dialogue')->json(), Textarea::make('rewards')->json()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('slug'), TextColumn::make('status')->badge()])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListQuests::route('/'), 'create' => CreateQuest::route('/create'), 'edit' => EditQuest::route('/{record}/edit')];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
