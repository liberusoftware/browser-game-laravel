<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreLivewire\Livewire;

use Liberu\BrowserGame\GameCore\Models\GameWorld;
use Liberu\BrowserGame\GameCore\Queries\GameCoreOverview as OverviewQuery;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;
use Livewire\Component;

final class WorldOverview extends Component
{
    public string $worldId;

    public ?string $message = null;

    public function mount(string $worldId): void
    {
        $this->worldId = $worldId;
    }

    public function setClock(string $currentAt, string $speed = '1', bool $paused = false): void
    {
        $context = $this->context();
        app(GameCoreManager::class)->setClock($context, $this->world(), $currentAt, $speed, $paused);
        $this->message = 'Game clock updated.';
    }

    public function publishRuleset(int $version, array $rules = []): void
    {
        $context = $this->context();
        app(GameCoreManager::class)->publishRuleset($context, $this->world(), $version, $rules);
        $this->message = 'Ruleset published.';
    }

    public function publishContent(int $version, string $contentHash, array $manifest = []): void
    {
        $context = $this->context();
        app(GameCoreManager::class)->publishContentVersion($context, $this->world(), $version, $contentHash, $manifest);
        $this->message = 'Content version published.';
    }

    public function setFeatureFlag(string $key, bool $enabled, int $rolloutPercentage = 100, array $constraints = []): void
    {
        app(GameCoreManager::class)->setFeatureFlag($this->context(), $this->world(), $key, $enabled, $rolloutPercentage, $constraints);
        $this->message = 'Feature flag updated.';
    }

    public function setMaintenance(string $status, ?string $message = null): void
    {
        app(GameCoreManager::class)->setMaintenance($this->context(), $this->world(), $status, $message);
        $this->message = 'Maintenance state updated.';
    }

    public function render(): mixed
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $overview = app(OverviewQuery::class)->forWorld(new ArrayGameCoreContext(
            actor: $user?->getAuthIdentifier() === null ? null : (string) $user->getAuthIdentifier(),
            tenant: $team?->getAttribute('tenant_id'),
            team: $team?->getKey() === null ? null : (string) $team->getKey(),
        ), $this->worldId);

        return resolve('view')->make('browser-game-game-core-livewire::world-overview', ['overview' => $overview]);
    }

    private function context(): ArrayGameCoreContext
    {
        abort_unless(auth()->check(), 403);
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return new ArrayGameCoreContext(actor: (string) auth()->id(), tenant: $team?->getAttribute('tenant_id'), team: $team?->getKey() === null ? null : (string) $team->getKey());
    }

    private function world(): GameWorld
    {
        $context = $this->context();

        return GameWorld::query()->whereKey($this->worldId)->when($context->tenantId() !== null, fn ($query) => $query->where('tenant_id', $context->tenantId()))->when($context->teamId() !== null, fn ($query) => $query->where('team_id', $context->teamId()))->firstOrFail();
    }
}
