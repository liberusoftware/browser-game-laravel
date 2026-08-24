<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsLivewire\Livewire;

use Liberu\BrowserGame\Collections\Queries\CollectionsQuery;
use Livewire\Component;

final class CollectionsCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $collections = app(CollectionsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-collections-livewire::collections-catalog', ['collections' => $collections]);
    }
}
