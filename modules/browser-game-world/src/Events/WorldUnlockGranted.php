<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class WorldUnlockGranted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $unlockId, public string $actorId, public string $unlockKey) {}
}
