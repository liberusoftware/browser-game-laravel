<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class EconomyFeeCharged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $listingId, public string $currencyCode, public int $amount, public string $source) {}
}
