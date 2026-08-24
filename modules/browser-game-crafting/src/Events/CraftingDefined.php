<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class CraftingDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
