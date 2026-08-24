<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsLivewire\Livewire;

use Liberu\BrowserGame\LiveOps\Queries\LiveOpsQuery;
use Livewire\Component;

final class LiveOpsCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $liveOps = app(LiveOpsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-live-ops-livewire::live-ops-catalog', ['live-ops' => $liveOps]);
    }
}
