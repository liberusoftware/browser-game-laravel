<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Policies;

use Liberu\BrowserGame\World\Models\WorldEntity;

final class WorldPolicy
{
    public function view(?string $tenantId, ?string $teamId, WorldEntity $entity): bool
    {
        return ($entity->getAttribute('tenant_id') === null || $entity->getAttribute('tenant_id') === $tenantId)
            && ($entity->getAttribute('team_id') === null || $entity->getAttribute('team_id') === $teamId);
    }

    public function manage(?string $actorId, ?string $tenantId, ?string $teamId, WorldEntity $entity): bool
    {
        return $actorId !== null && $this->view($tenantId, $teamId, $entity);
    }
}
