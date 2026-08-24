<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class CraftingCompleted
{
    use Dispatchable;

    public function __construct(public string $queueId, public string $actorId, public int $quality) {}
}
