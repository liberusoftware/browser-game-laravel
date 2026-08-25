<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CraftingDiscovered implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $recipeId, public string $actorId) {}
}
