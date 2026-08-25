<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\GameCore\Models\GameFeatureFlag;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource\Pages\CreateGameFeatureFlag;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource\Pages\EditGameFeatureFlag;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource\Pages\ListGameFeatureFlags;

final class GameFeatureFlagResource extends Resource
{
    protected static ?string $model = GameFeatureFlag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Browser Game';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('world_id')->label('World')->options(fn (): array => GameWorldResource::getEloquentQuery()->pluck('name', 'id')->all())->searchable()->nullable(),
            TextInput::make('key')->required()->maxLength(120),
            Toggle::make('enabled'),
            TextInput::make('rollout_percentage')->numeric()->minValue(0)->maxValue(100)->required(),
            Textarea::make('constraints')->json()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('key')->searchable()->sortable(),
            TextColumn::make('world.name')->label('World')->placeholder('Global'),
            IconColumn::make('enabled')->boolean(),
            TextColumn::make('rollout_percentage')->suffix('%')->sortable(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([EditAction::make(), DeleteAction::make()])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return parent::getEloquentQuery()->where(function (Builder $query) use ($team): void {
            $query->whereNull('world_id')->orWhereHas('world', function (Builder $world) use ($team): void {
                $world->where(fn (Builder $scope): Builder => $scope->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
                    ->where(fn (Builder $scope): Builder => $scope->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
            });
        });
    }

    public static function getPages(): array
    {
        return ['index' => ListGameFeatureFlags::route('/'), 'create' => CreateGameFeatureFlag::route('/create'), 'edit' => EditGameFeatureFlag::route('/{record}/edit')];
    }
}
