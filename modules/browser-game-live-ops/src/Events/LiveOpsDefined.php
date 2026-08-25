<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class LiveOpsDefined implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
