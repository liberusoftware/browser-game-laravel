<?php

namespace App\Filament\App\Widgets;

use App\Models\Player_Item;
use App\Settings\GameSettings;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InventoryWidget extends Widget
{
    /** @var view-string */
    protected string $view = 'filament.app.widgets.inventory-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $player = $user?->player;

        if (! $player) {
            return [
                'items' => collect(),
                'maxSlots' => app(GameSettings::class)->max_inventory_size,
            ];
        }

        $items = Player_Item::where('player_id', $player->id)
            ->with('item')
            ->get();

        return [
            'items' => $items,
            'maxSlots' => app(GameSettings::class)->max_inventory_size,
        ];
    }
}
