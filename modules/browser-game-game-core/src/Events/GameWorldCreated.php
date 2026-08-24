<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class GameWorldCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $worldId, public ?string $actorId) {}
}
