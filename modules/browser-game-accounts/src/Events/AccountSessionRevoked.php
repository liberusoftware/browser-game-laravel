<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class AccountSessionRevoked
{
    use Dispatchable;

    public function __construct(public string $accountId, public int $sessionId, public ?string $actorId = null) {}
}
