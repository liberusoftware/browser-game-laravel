<?php

namespace App\Filament\Admin\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    /** @var view-string */
    protected string $view = 'filament.admin.widgets.quick-actions-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getActions(): array
    {
        $tenant = Filament::getTenant();

        return [
            [
                'label' => 'Create New Quest',
                'icon' => 'heroicon-o-map',
                'url' => $tenant ? route('filament.admin.resources.quests.create', ['tenant' => $tenant]) : '#',
                'color' => 'primary',
            ],
            [
                'label' => 'Add New Item',
                'icon' => 'heroicon-o-cube',
                'url' => $tenant ? route('filament.admin.resources.items.create', ['tenant' => $tenant]) : '#',
                'color' => 'success',
            ],
            [
                'label' => 'Manage Players',
                'icon' => 'heroicon-o-user-group',
                'url' => $tenant ? route('filament.admin.resources.players.index', ['tenant' => $tenant]) : '#',
                'color' => 'info',
            ],
            [
                'label' => 'Game Settings',
                'icon' => 'heroicon-o-cog-8-tooth',
                'url' => $tenant ? route('filament.admin.pages.manage-game-settings', ['tenant' => $tenant]) : '#',
                'color' => 'warning',
            ],
        ];
    }
}
