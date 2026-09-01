<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Admin\Resources\ModuleResource\Pages\ViewModule;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

class ModuleResource extends Resource
{
    protected static ?string $model = null;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string|\UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'Modules';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->disabled(),
                TextInput::make('version')
                    ->disabled(),
                Textarea::make('description')
                    ->disabled(),
                Toggle::make('enabled')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50),
                IconColumn::make('enabled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('dependencies')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->limit(30),
            ])
            ->filters([
                TernaryFilter::make('enabled')
                    ->label('Status')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled')
                    ->queries(
                        true: fn (Builder $query) => $query->where('enabled', true),
                        false: fn (Builder $query) => $query->where('enabled', false),
                    ),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        $registry = app(ModuleRegistry::class);
        $modules = array_map(
            fn ($manifest): array => [
                'name' => $manifest->name(),
                'version' => $manifest->version(),
                'description' => $manifest->displayName(),
                'enabled' => $registry->enabled(
                    $manifest->name(),
                    (array) config('modules.enabled', []),
                    (array) config('modules.disabled', []),
                ),
                'dependencies' => array_keys($manifest->requiredPackages()),
            ],
            array_values($registry->all()),
        );

        // Convert modules array to a collection that can be used with Filament
        $query = new class($modules) extends Builder
        {
            protected $modules;

            public function __construct($modules)
            {
                $this->modules = collect($modules);
            }

            public function get($columns = ['*'])
            {
                return $this->modules->map(function ($module) {
                    return (object) $module;
                });
            }

            public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
            {
                return $this->modules->map(function ($module) {
                    return (object) $module;
                });
            }

            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if ($column === 'enabled') {
                    $this->modules = $this->modules->filter(function ($module) use ($value) {
                        return $module['enabled'] === $value;
                    });
                }

                return $this;
            }
        };

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'view' => ViewModule::route('/{record}'),
        ];
    }
}
