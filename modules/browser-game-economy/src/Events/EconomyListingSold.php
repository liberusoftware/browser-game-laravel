<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class EconomyListingSold
{
    use Dispatchable;

    public function __construct(public int $listingId, public string $buyerId, public string $sellerId, public int $total) {}
}
