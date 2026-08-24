<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsLivewire\Livewire;

use Liberu\BrowserGame\Items\Queries\ItemsQuery;
use Livewire\Component;

final class ItemsCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $items = app(ItemsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-items-livewire::items-catalog', ['items' => $items]);
    }
}
