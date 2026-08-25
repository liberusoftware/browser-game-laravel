<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CombatBattleStarted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $battleId, public string $actorId, public string $opponentId) {}
}
