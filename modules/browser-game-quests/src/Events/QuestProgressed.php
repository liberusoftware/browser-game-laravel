<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class QuestProgressed
{
    use Dispatchable;

    public function __construct(public string $questId, public string $actorId, public string $status) {}
}
