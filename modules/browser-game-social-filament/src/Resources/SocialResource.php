<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialFilament\Resources;

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
use Liberu\BrowserGame\Social\Models\SocialRecord;

final class SocialResource extends Resource
{
    protected static ?string $model = SocialRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), Select::make('kind')->options(['friend' => 'Friend', 'party' => 'Party', 'chat' => 'Chat', 'mail' => 'Mail', 'guild' => 'Guild', 'alliance' => 'Alliance', 'report' => 'Report'])->required(), Select::make('status')->options(['active' => 'Active', 'open' => 'Open', 'accepted' => 'Accepted', 'declined' => 'Declined', 'sent' => 'Sent'])->required(), TextInput::make('owner_id'), TextInput::make('team_id'), KeyValue::make('data')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Resources\SocialResource\Pages\ListSocial::route('/'), 'create' => Resources\SocialResource\Pages\CreateSocial::route('/create'), 'edit' => Resources\SocialResource\Pages\EditSocial::route('/{record}/edit')];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
