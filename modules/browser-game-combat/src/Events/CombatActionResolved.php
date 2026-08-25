<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CombatActionResolved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $battleId, public string $actionId, public int $turn, public int $value) {}
}
