<?php

namespace App\Filament\Admin\Resources\PlayerResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'resources';

    protected static ?string $title = 'Player Resources';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('resource_type')
                    ->options([
                        'gold' => 'Gold',
                        'wood' => 'Wood',
                        'stone' => 'Stone',
                        'iron' => 'Iron',
                        'food' => 'Food',
                        'energy' => 'Energy',
                        'gems' => 'Gems',
                    ])
                    ->required()
                    ->label('Resource Type'),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resource_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'gold' => 'warning',
                        'wood' => 'success',
                        'stone' => 'gray',
                        'iron' => 'info',
                        'food' => 'primary',
                        'energy' => 'danger',
                        'gems' => 'purple',
                        default => 'gray',
                    })
                    ->sortable()
                    ->label('Resource Type'),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Modified')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('resource_type')
                    ->options([
                        'gold' => 'Gold',
                        'wood' => 'Wood',
                        'stone' => 'Stone',
                        'iron' => 'Iron',
                        'food' => 'Food',
                        'energy' => 'Energy',
                        'gems' => 'Gems',
                    ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
