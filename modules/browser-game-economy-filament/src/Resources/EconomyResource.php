<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyFilament\Resources;

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
use Liberu\BrowserGame\Economy\Models\EconomyRecord;
use Liberu\BrowserGame\EconomyFilament\Resources\EconomyResource\Pages\CreateEconomy;
use Liberu\BrowserGame\EconomyFilament\Resources\EconomyResource\Pages\EditEconomy;
use Liberu\BrowserGame\EconomyFilament\Resources\EconomyResource\Pages\ListEconomy;

final class EconomyResource extends Resource
{
    protected static ?string $model = EconomyRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(30),
            Select::make('kind')->options(['currency' => 'Currency', 'vendor' => 'Vendor', 'listing' => 'Listing', 'auction' => 'Auction'])->required()->default('currency'),
            TextInput::make('precision')->numeric()->minValue(0)->maxValue(6)->default(0),
            TextInput::make('fee_basis_points')->numeric()->minValue(0)->maxValue(10000)->default(0),
            Select::make('status')->options(['active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived'])->required(),
            TextInput::make('team_id'),
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
            'index' => ListEconomy::route('/'),
            'create' => CreateEconomy::route('/create'),
            'edit' => EditEconomy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
