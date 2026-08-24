<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Characters\Models\GameCharacter;

final class CharacterQuery
{
    public function forPlayer(string $playerId, ?string $worldId = null): Builder
    {
        return GameCharacter::query()->where('player_id', $playerId)->when($worldId, fn (Builder $query): Builder => $query->where('world_id', $worldId));
    }

    public function levelForExperience(int $experience): int
    {
        return max(1, intdiv(max(0, $experience), (int) config('browser-game.characters.experience_multiplier', 100)) + 1);
    }
}
