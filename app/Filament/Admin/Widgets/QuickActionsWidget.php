<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    /** @var view-string */
    protected string $view = 'filament.admin.widgets.quick-actions-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getActions(): array
    {
        return [
            [
                'label' => 'Create New Quest',
                'icon' => 'heroicon-o-map',
                'url' => route('filament.admin.resources.quests.create'),
                'color' => 'primary',
            ],
            [
                'label' => 'Add New Item',
                'icon' => 'heroicon-o-cube',
                'url' => route('filament.admin.resources.items.create'),
                'color' => 'success',
            ],
            [
                'label' => 'Manage Players',
                'icon' => 'heroicon-o-user-group',
                'url' => route('filament.admin.resources.players.index'),
                'color' => 'info',
            ],
            [
                'label' => 'Game Settings',
                'icon' => 'heroicon-o-cog-8-tooth',
                'url' => route('filament.admin.pages.manage-game-settings'),
                'color' => 'warning',
            ],
        ];
    }
}
