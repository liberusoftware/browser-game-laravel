<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class WorldTravelled
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $travelId, public string $actorId, public string $destinationId) {}
}
