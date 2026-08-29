<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Collections\Models\CollectionsRecord;

final class CollectionsQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return CollectionsRecord::query()
            ->where(fn (Builder $q): Builder => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where(fn (Builder $q): Builder => $q->whereNull('team_id')->orWhere('team_id', $teamId));
    }

    public function availableAchievements(?string $tenantId, ?string $teamId): Builder
    {
        return $this->visible($tenantId, $teamId)
            ->where('kind', 'achievement')
            ->where('status', 'active');
    }

    public function forActor(string $actorId, ?string $tenantId, ?string $teamId): Builder
    {
        return $this->visible($tenantId, $teamId)->whereHas('progress', function (Builder $query) use ($actorId): void {
            $query->where('actor_id', $actorId);
        });
    }

    public function unlockedForActor(string $actorId, ?string $tenantId, ?string $teamId): Builder
    {
        return $this->forActor($actorId, $tenantId, $teamId)->whereHas('progress', function (Builder $query) use ($actorId): void {
            $query->where('actor_id', $actorId)->whereNotNull('completed_at');
        });
    }
}
