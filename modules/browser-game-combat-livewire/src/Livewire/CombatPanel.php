<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatLivewire\Livewire;

use Liberu\BrowserGame\Combat\Queries\CombatQuery;
use Livewire\Component;

final class CombatPanel extends Component
{
    public function render(): mixed
    {
        $user = auth()->user();
        $team = method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $battles = app(CombatQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-combat-livewire::combat-panel', ['battles' => $battles]);
    }
}
