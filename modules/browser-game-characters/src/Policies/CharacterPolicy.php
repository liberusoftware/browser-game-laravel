<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters\Policies;

use Liberu\BrowserGame\Characters\Models\GameCharacter;

final class CharacterPolicy
{
    public function view(?string $actorId, ?string $teamId, GameCharacter $character): bool
    {
        return $actorId !== null && $character->player_id === $actorId && $this->sameTeam($teamId, $character);
    }

    public function manage(?string $actorId, ?string $teamId, GameCharacter $character): bool
    {
        return $this->view($actorId, $teamId, $character);
    }

    private function sameTeam(?string $teamId, GameCharacter $character): bool
    {
        return $character->team_id === null || $character->team_id === $teamId;
    }
}
