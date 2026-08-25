<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Commerce\Models\CommerceRecord;
use Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource\Pages\CreateCommerce;
use Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource\Pages\EditCommerce;
use Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource\Pages\ListCommerce;

final class CommerceResource extends Resource
{
    protected static ?string $model = CommerceRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('status')->required(), TextInput::make('team_id'), KeyValue::make('data')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('team_id'),
        ])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommerce::route('/'),
            'create' => CreateCommerce::route('/create'),
            'edit' => EditCommerce::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
