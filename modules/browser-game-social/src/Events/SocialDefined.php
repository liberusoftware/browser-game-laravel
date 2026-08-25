<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class SocialDefined implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
