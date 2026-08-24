<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class EconomyDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
