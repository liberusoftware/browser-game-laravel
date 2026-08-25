<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsFilament\Resources;

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
use Liberu\BrowserGame\Collections\Models\CollectionsRecord;
use Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource\Pages\CreateCollections;
use Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource\Pages\EditCollections;
use Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource\Pages\ListCollections;

final class CollectionsResource extends Resource
{
    protected static ?string $model = CollectionsRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('kind')->required()->options(['achievement' => 'Achievement', 'title' => 'Title', 'reputation' => 'Reputation', 'pet' => 'Pet', 'mount' => 'Mount', 'housing' => 'Housing', 'cosmetic' => 'Cosmetic']),
            Select::make('status')->required()->options(['active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived']),
            TextInput::make('team_id'),
            KeyValue::make('data'),
        ])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('kind')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('repeatable')->boolean(),
            TextColumn::make('team_id'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollections::route('/create'),
            'edit' => EditCollections::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
