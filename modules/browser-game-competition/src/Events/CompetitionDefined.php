<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class CompetitionDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
