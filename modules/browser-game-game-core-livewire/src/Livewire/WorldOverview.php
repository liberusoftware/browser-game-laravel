<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreLivewire\Livewire;

use Liberu\BrowserGame\GameCore\Queries\GameCoreOverview as OverviewQuery;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Livewire\Component;

final class WorldOverview extends Component
{
    public string $worldId;

    public function mount(string $worldId): void
    {
        $this->worldId = $worldId;
    }

    public function render(): mixed
    {
        $user = auth()->user();
        $team = method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $overview = app(OverviewQuery::class)->forWorld(new ArrayGameCoreContext(
            actor: $user?->getAuthIdentifier() === null ? null : (string) $user->getAuthIdentifier(),
            tenant: $team?->getAttribute('tenant_id'),
            team: $team?->getKey() === null ? null : (string) $team->getKey(),
        ), $this->worldId);

        return resolve('view')->make('browser-game-game-core-livewire::world-overview', ['overview' => $overview]);
    }
}
