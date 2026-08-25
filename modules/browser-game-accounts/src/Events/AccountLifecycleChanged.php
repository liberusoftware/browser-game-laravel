<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class AccountLifecycleChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $accountId, public string $status, public ?string $actorId = null) {}
}
