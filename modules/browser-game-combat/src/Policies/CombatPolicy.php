<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Policies;

use Liberu\BrowserGame\Combat\Models\CombatBattle;

final class CombatPolicy
{
    public function view(mixed $user, CombatBattle $battle): bool
    {
        return (string) $user->getKey() === (string) $battle->getAttribute('actor_id') || (string) $user->getKey() === (string) $battle->getAttribute('opponent_id');
    }

    public function act(mixed $user, CombatBattle $battle): bool
    {
        return (string) $user->getKey() === (string) $battle->getAttribute('actor_id');
    }
}
