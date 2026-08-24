<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class CharacterCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $characterId, public string $playerId) {}
}
