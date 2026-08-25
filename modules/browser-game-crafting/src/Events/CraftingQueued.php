<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CraftingQueued implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $queueId, public string $actorId, public string $recipeId) {}
}
