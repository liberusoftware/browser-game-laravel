<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\GameCore\Contracts\GameCoreContext;
use Liberu\BrowserGame\GameCore\Events\GameClockChanged;
use Liberu\BrowserGame\GameCore\Events\GameContentVersionPublished;
use Liberu\BrowserGame\GameCore\Events\GameFeatureFlagChanged;
use Liberu\BrowserGame\GameCore\Events\GameMaintenanceStateChanged;
use Liberu\BrowserGame\GameCore\Events\GameRulesetPublished;
use Liberu\BrowserGame\GameCore\Events\GameWorldCreated;
use Liberu\BrowserGame\GameCore\Events\GameWorldUpdated;
use Liberu\BrowserGame\GameCore\Models\GameClock;
use Liberu\BrowserGame\GameCore\Models\GameContentVersion;
use Liberu\BrowserGame\GameCore\Models\GameFeatureFlag;
use Liberu\BrowserGame\GameCore\Models\GameMaintenanceState;
use Liberu\BrowserGame\GameCore\Models\GameRuleset;
use Liberu\BrowserGame\GameCore\Models\GameWorld;

final class GameCoreManager
{
    public function createWorld(GameCoreContext $context, string $name, string $slug, array $metadata = []): GameWorld
    {
        $this->assertActor($context);
        $this->assertText($name, 'name');
        $this->assertText($slug, 'slug');
        $world = DB::transaction(fn (): GameWorld => GameWorld::query()->create([
            'id' => (string) Str::uuid(), 'tenant_id' => $context->tenantId(), 'team_id' => $context->teamId(),
            'name' => $name, 'slug' => $slug, 'status' => 'draft', 'metadata' => $metadata,
        ]));
        GameWorldCreated::dispatch((string) $world->getKey(), $context->actorId());

        return $world;
    }

    public function setClock(GameCoreContext $context, GameWorld $world, string $currentAt, string $speed = '1', bool $paused = false): GameClock
    {
        $this->assertScope($context, $world);
        if (! is_numeric($speed) || (float) $speed < 0) {
            throw ValidationException::withMessages(['speed' => 'Clock speed must be a non-negative number.']);
        }
        $clock = DB::transaction(fn (): GameClock => GameClock::query()->updateOrCreate(
            ['world_id' => $world->id], ['current_at' => $currentAt, 'speed' => $speed, 'paused' => $paused, 'updated_by' => $context->actorId()],
        ));
        GameClockChanged::dispatch((string) $world->getKey(), $context->actorId());

        return $clock;
    }

    public function updateWorld(GameCoreContext $context, GameWorld $world, string $name, string $status, array $metadata = []): GameWorld
    {
        $this->assertScope($context, $world);
        $this->assertText($name, 'name');
        if (! in_array($status, ['draft', 'active', 'archived'], true)) {
            throw ValidationException::withMessages(['status' => 'World status is invalid.']);
        }

        $updated = DB::transaction(function () use ($context, $world, $name, $status, $metadata): GameWorld {
            $current = GameWorld::query()->whereKey($world->getKey())->lockForUpdate()->firstOrFail();
            $this->assertScope($context, $current);
            $current->update(['name' => $name, 'status' => $status, 'metadata' => $metadata]);

            return $current->refresh();
        });
        GameWorldUpdated::dispatch((string) $updated->getKey(), $status, $context->actorId());

        return $updated;
    }

    public function publishRuleset(GameCoreContext $context, GameWorld $world, int $version, array $rules): GameRuleset
    {
        $this->assertScope($context, $world);
        $this->assertVersion($version);
        $ruleset = DB::transaction(function () use ($context, $world, $version, $rules): GameRuleset {
            $current = GameWorld::query()->whereKey($world->getKey())->lockForUpdate()->firstOrFail();
            $this->assertScope($context, $current);
            GameRuleset::query()->where('world_id', $current->id)->where('status', 'published')->update(['status' => 'archived']);

            return GameRuleset::query()->updateOrCreate(
                ['world_id' => $current->id, 'version' => $version],
                ['status' => 'published', 'rules' => $rules, 'published_at' => now(), 'published_by' => $context->actorId()],
            );
        });
        GameRulesetPublished::dispatch((string) $world->getKey(), $version, $context->actorId());

        return $ruleset;
    }

    public function publishContentVersion(GameCoreContext $context, GameWorld $world, int $version, string $contentHash, array $manifest): GameContentVersion
    {
        $this->assertScope($context, $world);
        $this->assertVersion($version);
        $this->assertText($contentHash, 'content_hash');
        $content = DB::transaction(function () use ($context, $world, $version, $contentHash, $manifest): GameContentVersion {
            $current = GameWorld::query()->whereKey($world->getKey())->lockForUpdate()->firstOrFail();
            $this->assertScope($context, $current);
            GameContentVersion::query()->where('world_id', $current->id)->where('status', 'published')->update(['status' => 'archived']);

            return GameContentVersion::query()->updateOrCreate(
                ['world_id' => $current->id, 'version' => $version],
                ['status' => 'published', 'content_hash' => $contentHash, 'manifest' => $manifest, 'published_at' => now(), 'published_by' => $context->actorId()],
            );
        });
        GameContentVersionPublished::dispatch((string) $world->getKey(), $version, $context->actorId());

        return $content;
    }

    public function setFeatureFlag(GameCoreContext $context, ?GameWorld $world, string $key, bool $enabled, int $rolloutPercentage = 100, array $constraints = []): GameFeatureFlag
    {
        $this->assertActor($context);
        if ($world !== null) {
            $this->assertScope($context, $world);
        }
        $this->assertText($key, 'key');
        if ($rolloutPercentage < 0 || $rolloutPercentage > 100) {
            throw ValidationException::withMessages(['rollout_percentage' => 'Rollout must be between 0 and 100.']);
        }
        $flag = DB::transaction(fn (): GameFeatureFlag => GameFeatureFlag::query()->updateOrCreate(
            ['world_id' => $world?->id, 'key' => $key],
            ['enabled' => $enabled, 'rollout_percentage' => $rolloutPercentage, 'constraints' => $constraints, 'changed_by' => $context->actorId()],
        ));
        GameFeatureFlagChanged::dispatch($world?->getKey() === null ? null : (string) $world->getKey(), $key, $context->actorId());

        return $flag;
    }

    public function setMaintenance(GameCoreContext $context, GameWorld $world, string $status, ?string $message = null): GameMaintenanceState
    {
        $this->assertScope($context, $world);
        if (! in_array($status, ['scheduled', 'active', 'resolved'], true)) {
            throw ValidationException::withMessages(['status' => 'Maintenance status is invalid.']);
        }
        $maintenance = DB::transaction(fn (): GameMaintenanceState => GameMaintenanceState::query()->updateOrCreate(
            ['world_id' => $world->id],
            ['status' => $status, 'message' => $message, 'starts_at' => $status === 'active' ? now() : null, 'changed_by' => $context->actorId()],
        ));
        GameMaintenanceStateChanged::dispatch((string) $world->getKey(), $status, $context->actorId());

        return $maintenance;
    }

    private function assertScope(GameCoreContext $context, GameWorld $world): void
    {
        if (($world->tenant_id !== null && $world->tenant_id !== $context->tenantId()) || ($world->team_id !== null && $world->team_id !== $context->teamId()) || $context->actorId() === null) {
            throw ValidationException::withMessages(['world' => 'The world is not available in the current context.']);
        }
    }

    private function assertActor(GameCoreContext $context): void
    {
        if ($context->actorId() === null || trim($context->actorId()) === '') {
            throw ValidationException::withMessages(['actor' => 'An actor is required.']);
        }
    }

    private function assertText(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw ValidationException::withMessages([$field => 'This value is required.']);
        }
    }

    private function assertVersion(int $version): void
    {
        if ($version < 1) {
            throw ValidationException::withMessages(['version' => 'Version must be a positive integer.']);
        }
    }
}
