<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class EconomyTransactionRecorded
{
    use Dispatchable;

    public function __construct(public string $actorId, public string $currencyCode, public int $amount, public string $entryType) {}
}
