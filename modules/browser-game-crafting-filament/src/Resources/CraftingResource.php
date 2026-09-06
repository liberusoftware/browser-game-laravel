<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Crafting\Models\CraftingRecord;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource\Pages\CreateCrafting;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource\Pages\EditCrafting;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource\Pages\ListCrafting;

final class CraftingResource extends Resource
{
    protected static ?string $model = CraftingRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->maxLength(255),
            TextInput::make('profession')->maxLength(80),
            TextInput::make('min_level')->numeric()->minValue(1)->default(1),
            TextInput::make('success_rate')->numeric()->minValue(0)->maxValue(100)->default(100),
            TextInput::make('crafting_time_seconds')->numeric()->minValue(0)->default(0),
            TextInput::make('status')->required()->in(['active', 'archived']),
            KeyValue::make('materials')->required(),
            KeyValue::make('outputs')->required(),
            KeyValue::make('salvage'),
            KeyValue::make('data'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrafting::route('/'),
            'create' => CreateCrafting::route('/create'),
            'edit' => EditCrafting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
