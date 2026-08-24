<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Queries;

use Illuminate\Support\Collection;
use Liberu\BrowserGame\GameCore\Contracts\GameCoreContext;
use Liberu\BrowserGame\GameCore\Models\GameClock;
use Liberu\BrowserGame\GameCore\Models\GameContentVersion;
use Liberu\BrowserGame\GameCore\Models\GameFeatureFlag;
use Liberu\BrowserGame\GameCore\Models\GameMaintenanceState;
use Liberu\BrowserGame\GameCore\Models\GameRuleset;
use Liberu\BrowserGame\GameCore\Models\GameWorld;
use RuntimeException;

final class GameCoreOverview
{
    public function forWorld(GameCoreContext $context, string $worldId): array
    {
        $world = GameWorld::query()
            ->whereKey($worldId)
            ->where(fn ($query) => $query
                ->whereNull('tenant_id')->orWhere('tenant_id', $context->tenantId()))
            ->where(fn ($query) => $query
                ->whereNull('team_id')->orWhere('team_id', $context->teamId()))
            ->first();

        if ($world === null) {
            throw new RuntimeException('Game world not found in the current context.');
        }

        return [
            'world' => $world,
            'clock' => GameClock::query()->where('world_id', $world->id)->first(),
            'ruleset' => GameRuleset::query()->where('world_id', $world->id)->where('status', 'published')->latest('version')->first(),
            'content_version' => GameContentVersion::query()->where('world_id', $world->id)->where('status', 'published')->latest('version')->first(),
            'maintenance' => GameMaintenanceState::query()->where('world_id', $world->id)->first(),
            'feature_flags' => GameFeatureFlag::query()->where('world_id', $world->id)->get(),
        ];
    }

    public function enabledFlags(?string $worldId = null): Collection
    {
        return GameFeatureFlag::query()
            ->where('enabled', true)
            ->where('rollout_percentage', '>', 0)
            ->when($worldId, fn ($query) => $query->where(fn ($q) => $q->whereNull('world_id')->orWhere('world_id', $worldId)))
            ->get();
    }
}
