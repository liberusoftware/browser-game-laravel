<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class ModerationAndAnalyticsDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
