<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsFilament\Resources;

use Filament\Actions\Action;
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
use Liberu\BrowserGame\ModerationAndAnalytics\Models\ModerationAndAnalyticsRecord;
use Liberu\BrowserGame\ModerationAndAnalytics\Support\ModerationAndAnalyticsManager;

final class ModerationAndAnalyticsResource extends Resource
{
    protected static ?string $model = ModerationAndAnalyticsRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), Select::make('kind')->options(['report' => 'Report', 'sanction' => 'Sanction', 'appeal' => 'Appeal', 'telemetry' => 'Telemetry', 'funnel' => 'Funnel', 'balance' => 'Balance', 'economy' => 'Economy', 'fraud' => 'Fraud', 'health' => 'Health'])->required(), Select::make('status')->options(['open' => 'Open', 'active' => 'Active', 'recorded' => 'Recorded', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed', 'revoked' => 'Revoked'])->required(), TextInput::make('target_id'), TextInput::make('team_id'), KeyValue::make('data')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')])->actions([
            Action::make('resolve')->requiresConfirmation()->action(fn (ModerationAndAnalyticsRecord $record): ModerationAndAnalyticsRecord => app(ModerationAndAnalyticsManager::class)->resolve($record))->visible(fn (ModerationAndAnalyticsRecord $record): bool => in_array($record->kind, ['report', 'sanction', 'appeal', 'fraud', 'health'], true) && in_array($record->status, ['open', 'active'], true)),
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Resources\ModerationAndAnalyticsResource\Pages\ListModerationAndAnalytics::route('/'), 'create' => Resources\ModerationAndAnalyticsResource\Pages\CreateModerationAndAnalytics::route('/create'), 'edit' => Resources\ModerationAndAnalyticsResource\Pages\EditModerationAndAnalytics::route('/{record}/edit')];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
