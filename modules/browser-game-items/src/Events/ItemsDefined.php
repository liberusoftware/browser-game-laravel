<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class ItemsDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
