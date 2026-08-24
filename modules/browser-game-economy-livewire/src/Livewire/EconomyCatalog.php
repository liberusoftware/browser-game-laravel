<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyLivewire\Livewire;

use Liberu\BrowserGame\Economy\Queries\EconomyQuery;
use Livewire\Component;

final class EconomyCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $economy = app(EconomyQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-economy-livewire::economy-catalog', ['economy' => $economy]);
    }
}
