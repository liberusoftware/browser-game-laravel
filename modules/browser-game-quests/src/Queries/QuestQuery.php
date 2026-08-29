<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Quests\Models\Quest;

final class QuestQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return Quest::query()
            ->where(fn (Builder $q): Builder => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where(fn (Builder $q): Builder => $q->whereNull('team_id')->orWhere('team_id', $teamId));
    }

    public function availableFor(string $actorId, ?string $tenantId, ?string $teamId): Builder
    {
        return $this->visible($tenantId, $teamId)
            ->where('status', 'active')
            ->where(function (Builder $query) use ($actorId, $tenantId, $teamId): void {
                $query->whereDoesntHave('progress', function (Builder $progress) use ($actorId, $tenantId, $teamId): void {
                    $progress->where('actor_id', $actorId)->where('tenant_id', $tenantId)->where('team_id', $teamId);
                })->orWhere(function (Builder $repeatable) use ($actorId, $tenantId, $teamId): void {
                    $repeatable->where('repeatable', true)->whereDoesntHave('progress', function (Builder $progress) use ($actorId, $tenantId, $teamId): void {
                        $progress->where('actor_id', $actorId)->where('tenant_id', $tenantId)->where('team_id', $teamId)->where('status', 'in_progress');
                    });
                });
            });
    }

    public function activeFor(string $actorId, ?string $tenantId, ?string $teamId): Builder
    {
        return $this->visible($tenantId, $teamId)->whereHas('progress', function (Builder $query) use ($actorId, $tenantId, $teamId): void {
            $query->where('actor_id', $actorId)->where('tenant_id', $tenantId)->where('team_id', $teamId)->where('status', 'in_progress');
        });
    }

    public function completedFor(string $actorId, ?string $tenantId, ?string $teamId): Builder
    {
        return $this->visible($tenantId, $teamId)->whereHas('progress', function (Builder $query) use ($actorId, $tenantId, $teamId): void {
            $query->where('actor_id', $actorId)->where('tenant_id', $tenantId)->where('team_id', $teamId)->where('status', 'completed');
        });
    }
}
