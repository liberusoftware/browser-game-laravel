<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CompetitionDefined implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
