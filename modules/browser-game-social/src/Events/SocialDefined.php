<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class SocialDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
