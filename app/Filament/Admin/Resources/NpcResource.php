<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NpcResource\Pages\CreateNpc;
use App\Filament\Admin\Resources\NpcResource\Pages\EditNpc;
use App\Filament\Admin\Resources\NpcResource\Pages\ListNpcs;
use App\Models\Npc;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class NpcResource extends Resource
{
    protected static ?string $model = Npc::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Content';

    protected static ?string $navigationLabel = 'NPC encounters';

    protected static ?string $modelLabel = 'NPC encounter';

    protected static bool $isScopedToTenant = false;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Encounter')->description('NPCs are shared across the game. Deactivate an encounter to hide it from new battles while preserving battle history.')->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->alphaDash()->maxLength(255)->unique(ignoreRecord: true),
                Textarea::make('description')->required()->maxLength(2000),
                TextInput::make('region')->required()->maxLength(255),
                Select::make('difficulty')->options(['Training' => 'Training', 'Normal' => 'Normal', 'Elite' => 'Elite', 'Boss' => 'Boss'])->required(),
                Select::make('behavior')->options(['balanced' => 'Balanced', 'aggressive' => 'Aggressive', 'defensive' => 'Defensive', 'mystic' => 'Mystic'])->required(),
                TextInput::make('level')->numeric()->integer()->minValue(1)->maxValue(100)->required(),
                Toggle::make('is_active')->label('Available on the hunting board')->default(true),
            ])->columns(2),
            Section::make('Combat stats')->schema(array_map(
                fn (string $stat) => TextInput::make('stats.'.$stat)->label(ucfirst($stat))->numeric()->integer()->minValue($stat === 'mana' ? 0 : 1)->maxValue(10000)->required(),
                ['health', 'mana', 'strength', 'defense', 'agility', 'intelligence'],
            ))->columns(3),
            Section::make('Victory rewards')->schema([
                TextInput::make('experience_reward')->label('Experience')->numeric()->integer()->minValue(0)->maxValue(10000)->required(),
                TextInput::make('gold_reward')->label('Gold')->numeric()->integer()->minValue(0)->maxValue(10000)->required(),
                Select::make('loot_item_id')->label('Item reward')->relationship('lootItem', 'name')->searchable(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('region')->searchable(),
            TextColumn::make('level')->numeric()->sortable(),
            TextColumn::make('difficulty')->badge(),
            TextColumn::make('behavior')->badge(),
            IconColumn::make('is_active')->label('Active')->boolean(),
        ])->recordActions([EditAction::make()])->defaultSort('level');
    }

    public static function getPages(): array
    {
        return ['index' => ListNpcs::route('/'), 'create' => CreateNpc::route('/create'), 'edit' => EditNpc::route('/{record}/edit')];
    }
}
