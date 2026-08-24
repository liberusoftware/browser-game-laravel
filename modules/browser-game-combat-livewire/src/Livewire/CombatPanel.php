<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatLivewire\Livewire;

use Liberu\BrowserGame\Combat\Models\CombatBattle;
use Liberu\BrowserGame\Combat\Queries\CombatQuery;
use Liberu\BrowserGame\Combat\Support\CombatManager;
use Livewire\Component;

final class CombatPanel extends Component
{
    public ?string $message = null;

    public function attack(string $battleId): void
    {
        abort_unless(auth()->check(), 403);
        $battle = CombatBattle::query()->findOrFail($battleId);
        app(CombatManager::class)->resolve($battle, (string) auth()->id(), 'attack', 10, 'livewire:'.auth()->id().':'.$battleId.':'.$battle->turn);
        $this->message = 'Combat action resolved.';
    }

    public function render(): mixed
    {
        $user = auth()->user();
        $team = method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $battles = app(CombatQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-combat-livewire::combat-panel', ['battles' => $battles]);
    }
}
