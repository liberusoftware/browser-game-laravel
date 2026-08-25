<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldLivewire\Livewire;

use Liberu\BrowserGame\World\Queries\WorldQuery;
use Liberu\BrowserGame\World\Support\WorldManager;
use Livewire\Component;

final class WorldCatalog extends Component
{
    public string $originId = '';

    public string $destinationId = '';

    public ?string $message = null;

    public function travel(string $originId, string $destinationId): void
    {
        abort_unless(auth()->check(), 403);
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $query = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $origin = $query->whereKey($originId)->where('status', 'active')->firstOrFail();
        $destination = $query->whereKey($destinationId)->where('status', 'active')->firstOrFail();
        app(WorldManager::class)->travel((string) auth()->id(), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $origin, $destination, 'livewire:travel:'.auth()->id().':'.$originId.':'.$destinationId);
        $this->message = 'Travel recorded.';
    }

    public function render(): mixed
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $entities = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->orderBy('kind')->orderBy('name')->get();

        return resolve('view')->make('browser-game-world-livewire::world-catalog', ['entities' => $entities]);
    }
}
