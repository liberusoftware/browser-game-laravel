<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatLivewire\Livewire;

use Liberu\BrowserGame\Combat\Queries\CombatQuery;
use Liberu\BrowserGame\Combat\Support\CombatManager;
use Livewire\Component;

final class CombatPanel extends Component
{
    public ?string $message = null;

    public array $simulation = [];

    public string $simulationOpponentId = '';

    public function attack(string $battleId): void
    {
        abort_unless(auth()->check(), 403);
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $battle = app(CombatQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($battleId)->where('actor_id', (string) auth()->id())->firstOrFail();
        app(CombatManager::class)->resolve($battle, (string) auth()->id(), 'attack', 10, 'livewire:'.auth()->id().':'.$battleId.':'.$battle->turn);
        $this->message = 'Combat action resolved.';
    }

    public function resolveAction(string $battleId, string $action, int $value = 0, array $effects = []): void
    {
        abort_unless(auth()->check(), 403);
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $battle = app(CombatQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($battleId)->where('actor_id', (string) auth()->id())->firstOrFail();
        app(CombatManager::class)->resolve($battle, (string) auth()->id(), $action, $value, 'livewire:'.auth()->id().':'.$battleId.':'.$action.':'.$battle->turn, $effects);
        $this->message = 'Combat action resolved.';
    }

    public function simulate(string $opponentId, array $actions = []): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(count($actions) <= 100, 422);
        $this->simulation = app(CombatManager::class)->simulate((string) auth()->id(), $opponentId, $actions);
        $this->message = 'Combat simulation complete.';
    }

    public function render(): mixed
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $battles = app(CombatQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-combat-livewire::combat-panel', ['battles' => $battles]);
    }
}
