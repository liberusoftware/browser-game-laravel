<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CollectionProgressRecorded implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $collectionId,
        public string $actorId,
        public string $entryKey,
        public int $quantity,
        public bool $completed,
    ) {}
}
