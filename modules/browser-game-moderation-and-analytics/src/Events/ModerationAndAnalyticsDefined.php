<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ModerationAndAnalyticsDefined implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
