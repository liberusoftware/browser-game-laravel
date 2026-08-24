<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class CommerceDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
