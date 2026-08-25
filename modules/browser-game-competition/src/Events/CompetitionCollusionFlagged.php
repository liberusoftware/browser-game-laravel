<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class CompetitionCollusionFlagged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $flagId, public string $competitionId, public string $actorId) {}
}
