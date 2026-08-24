<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class CollectionsDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
