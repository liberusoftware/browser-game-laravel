<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class AccountBanChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $accountId, public int $banId, public bool $active, public ?string $actorId = null) {}
}
