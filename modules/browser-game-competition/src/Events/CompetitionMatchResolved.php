<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class CompetitionMatchResolved implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $matchId, public string $winnerId, public string $loserId) {}
}
