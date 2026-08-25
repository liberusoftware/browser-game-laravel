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

    public function isEnabled(GameCoreContext $context, ?GameWorld $world, string $key, array $attributes = []): bool
    {
        return $this->enabledFor($context, $world, $attributes)->contains(fn (GameFeatureFlag $flag): bool => $flag->key === $key);
    }

    public function enabledFor(GameCoreContext $context, ?GameWorld $world, array $attributes = []): Collection
    {
        if ($context->actorId() === null || ($world !== null && ! $this->worldAvailable($context, $world))) {
            return collect();
        }

        $flags = GameFeatureFlag::query()
            ->where(fn ($query) => $query->whereNull('world_id')->when($world, fn ($nested) => $nested->orWhere('world_id', $world->getKey())))
            ->get()
            ->sortByDesc(fn (GameFeatureFlag $flag): int => $flag->world_id === null ? 0 : 1)
            ->unique('key')
            ->filter(fn (GameFeatureFlag $flag): bool => (bool) $flag->enabled && (int) $flag->rollout_percentage > 0)
            ->values();

        $contextAttributes = array_merge([
            'actor_id' => (string) $context->actorId(),
            'tenant_id' => $context->tenantId(),
            'team_id' => $context->teamId(),
        ], $attributes);

        return $flags->filter(fn (GameFeatureFlag $flag): bool => $this->matches($flag, $contextAttributes))->values();
    }

    private function matches(GameFeatureFlag $flag, array $attributes): bool
    {
        foreach ((array) $flag->constraints as $key => $expected) {
            $actual = $attributes[$key] ?? null;
            if (is_array($expected) ? ! in_array($actual, $expected, true) : $actual !== $expected) {
                return false;
            }
        }

        return ((int) hexdec(substr(hash('sha256', (string) $attributes['actor_id'].'|'.$flag->key), 0, 8)) % 100) < (int) $flag->rollout_percentage;
    }

    private function worldAvailable(GameCoreContext $context, GameWorld $world): bool
    {
        return ($world->tenant_id === null || (string) $world->tenant_id === (string) $context->tenantId())
            && ($world->team_id === null || (string) $world->team_id === (string) $context->teamId());
    }
}
