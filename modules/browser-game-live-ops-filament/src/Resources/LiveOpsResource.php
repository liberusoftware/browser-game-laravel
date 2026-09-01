<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsFilament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\LiveOps\Models\LiveOpsRecord;
use Liberu\BrowserGame\LiveOps\Support\LiveOpsManager;
use Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource\Pages\CreateLiveOps;
use Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource\Pages\EditLiveOps;
use Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource\Pages\ListLiveOps;

final class LiveOpsResource extends Resource
{
    protected static ?string $model = LiveOpsRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Live Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('kind')->required()->options([
                'daily_activity' => 'Daily activity', 'event' => 'Event', 'season' => 'Season',
                'schedule' => 'Content schedule', 'announcement' => 'Announcement', 'grant' => 'Grant',
            ]),
            Select::make('status')->required()->options(['draft' => 'Draft', 'paused' => 'Paused', 'published' => 'Published', 'archived' => 'Archived']),
            TextInput::make('team_id'),
            KeyValue::make('data')->helperText('Keep grants and schedule metadata versioned with the activity.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')])->actions([
            Action::make('publish')->requiresConfirmation()->action(fn (LiveOpsRecord $record): LiveOpsRecord => app(LiveOpsManager::class)->publish($record))->visible(fn (LiveOpsRecord $record): bool => in_array($record->status, ['draft', 'paused'], true)),
            Action::make('rollback')->requiresConfirmation()->form([
                Textarea::make('reason')->required()->maxLength(1000),
            ])->action(fn (LiveOpsRecord $record, array $data): LiveOpsRecord => app(LiveOpsManager::class)->rollback($record, (string) auth()->id(), $data['reason']))->visible(fn (LiveOpsRecord $record): bool => $record->status !== 'rolled_back'),
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLiveOps::route('/'), 'create' => CreateLiveOps::route('/create'), 'edit' => EditLiveOps::route('/{record}/edit')];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }
}
