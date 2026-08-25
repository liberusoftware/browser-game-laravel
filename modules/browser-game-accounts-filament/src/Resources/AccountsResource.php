<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsFilament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;
use Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource\Pages\CreateAccount;
use Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource\Pages\EditAccount;
use Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource\Pages\ListAccounts;

final class AccountsResource extends Resource
{
    protected static ?string $model = AccountsRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('username')->maxLength(50),
            TextInput::make('region')->maxLength(20),
            TextInput::make('birth_year')->numeric()->minValue(1900),
            TextInput::make('status')->required()->in(['active', 'suspended', 'closed']),
            TextInput::make('team_id'),
            KeyValue::make('data'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')])->actions([
            Action::make('suspend')->requiresConfirmation()->action(fn (AccountsRecord $record): AccountsRecord => app(AccountsManager::class)->suspend($record))->visible(fn (AccountsRecord $record): bool => $record->status === 'active'),
            Action::make('reactivate')->requiresConfirmation()->action(fn (AccountsRecord $record): AccountsRecord => app(AccountsManager::class)->reactivate($record))->visible(fn (AccountsRecord $record): bool => $record->status === 'suspended'),
            Action::make('ban')->requiresConfirmation()->form([TextInput::make('reason')->required()->maxLength(1000), DateTimePicker::make('ends_at')->minDate(now())])->action(function (AccountsRecord $record, array $data): void {
                app(AccountsManager::class)->ban($record, $data['reason'], $data['ends_at'] ?? null);
            }),
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
