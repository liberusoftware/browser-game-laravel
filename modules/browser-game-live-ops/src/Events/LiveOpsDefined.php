<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class LiveOpsDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
