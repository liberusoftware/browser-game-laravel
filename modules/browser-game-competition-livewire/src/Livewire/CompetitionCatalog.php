<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionLivewire\Livewire;

use Liberu\BrowserGame\Competition\Queries\CompetitionQuery;
use Livewire\Component;

final class CompetitionCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $competition = app(CompetitionQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-competition-livewire::competition-catalog', ['competition' => $competition]);
    }
}
