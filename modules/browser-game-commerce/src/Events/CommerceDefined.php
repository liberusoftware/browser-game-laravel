<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CommerceDefined implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
