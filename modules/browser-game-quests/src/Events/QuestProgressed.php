<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class QuestProgressed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $questId, public string $actorId, public string $status) {}
}
