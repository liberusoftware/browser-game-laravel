<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Policies;

use Liberu\BrowserGame\GameCore\Contracts\GameCoreContext;
use Liberu\BrowserGame\GameCore\Models\GameWorld;

final class GameCorePolicy
{
    public function view(GameCoreContext $context, GameWorld $world): bool
    {
        return $this->sameScope($context, $world);
    }

    public function manage(GameCoreContext $context, GameWorld $world): bool
    {
        return $this->sameScope($context, $world) && $context->actorId() !== null;
    }

    private function sameScope(GameCoreContext $context, GameWorld $world): bool
    {
        return ($world->tenant_id === null || $world->tenant_id === $context->tenantId())
            && ($world->team_id === null || $world->team_id === $context->teamId());
    }
}
