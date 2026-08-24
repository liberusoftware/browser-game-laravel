<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldLivewire\Livewire;

use Liberu\BrowserGame\World\Queries\WorldQuery;
use Livewire\Component;

final class WorldCatalog extends Component
{
    public function render(): mixed
    {
        $user = auth()->user();
        $team = method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $entities = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->orderBy('kind')->orderBy('name')->get();

        return resolve('view')->make('browser-game-world-livewire::world-catalog', ['entities' => $entities]);
    }
}
