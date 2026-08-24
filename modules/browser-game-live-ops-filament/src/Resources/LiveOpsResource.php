<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\BrowserGame\LiveOps\Models\LiveOpsRecord;

final class LiveOpsResource extends Resource
{
    protected static ?string $model = LiveOpsRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

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
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('team_id')]);
    }

    public static function getPages(): array
    {
        return ['index' => Resources\LiveOpsResource\Pages\ListLiveOps::route('/'), 'create' => Resources\LiveOpsResource\Pages\CreateLiveOps::route('/create'), 'edit' => Resources\LiveOpsResource\Pages\EditLiveOps::route('/{record}/edit')];
    }
}
