<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingLivewire\Livewire;

use Liberu\BrowserGame\Crafting\Queries\CraftingQuery;
use Livewire\Component;

final class CraftingCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $crafting = app(CraftingQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-crafting-livewire::crafting-catalog', ['crafting' => $crafting]);
    }
}
