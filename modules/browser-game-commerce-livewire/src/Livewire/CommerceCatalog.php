<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceLivewire\Livewire;

use Liberu\BrowserGame\Commerce\Queries\CommerceQuery;
use Livewire\Component;

final class CommerceCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $commerce = app(CommerceQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-commerce-livewire::commerce-catalog', ['commerce' => $commerce]);
    }
}
