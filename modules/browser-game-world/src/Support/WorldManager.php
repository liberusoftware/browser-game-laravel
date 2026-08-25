<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\World\Events\WorldEntityDefined;
use Liberu\BrowserGame\World\Events\WorldEntityUpdated;
use Liberu\BrowserGame\World\Events\WorldTravelled;
use Liberu\BrowserGame\World\Events\WorldUnlockGranted;
use Liberu\BrowserGame\World\Events\WorldUnlockRevoked;
use Liberu\BrowserGame\World\Models\WorldEntity;
use Liberu\BrowserGame\World\Models\WorldTravel;
use Liberu\BrowserGame\World\Models\WorldUnlock;

final class WorldManager
{
    public function defineRegion(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'region', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function defineLocation(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'location', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function defineMap(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'map', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function defineEncounter(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'encounter', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function defineNpc(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'npc', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function defineResource(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'resource', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function defineWeather(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'weather', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function defineUnlock(?string $tenantId, ?string $teamId, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        return $this->define($tenantId, $teamId, 'unlock', $name, $slug, $attributes, $worldId, $unlockKey);
    }

    public function define(?string $tenantId, ?string $teamId, string $kind, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        if (! in_array($kind, (array) config('browser-game.world.kinds', ['region', 'location', 'map', 'encounter', 'npc', 'resource', 'weather', 'unlock']), true)) {
            throw ValidationException::withMessages(['kind' => 'World entity kind is invalid.']);
        }
        foreach (['name' => $name, 'slug' => $slug] as $field => $value) {
            if (trim($value) === '') {
                throw ValidationException::withMessages([$field => 'This value is required.']);
            }
        }
        $entity = DB::transaction(fn (): WorldEntity => WorldEntity::query()->create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'team_id' => $teamId, 'world_id' => $worldId,
            'kind' => $kind, 'name' => $name, 'slug' => $slug, 'status' => 'active', 'attributes' => $attributes, 'unlock_key' => $unlockKey,
        ]));
        WorldEntityDefined::dispatch((string) $entity->getKey(), $kind);

        return $entity;
    }

    public function travel(string $actorId, ?string $tenantId, ?string $teamId, ?WorldEntity $origin, WorldEntity $destination, ?string $idempotencyKey = null, array $metadata = []): WorldTravel
    {
        if (trim($actorId) === '') {
            throw ValidationException::withMessages(['actor' => 'An actor is required.']);
        }
        $this->assertScope($destination, $tenantId, $teamId);
        if ($origin !== null) {
            $this->assertScope($origin, $tenantId, $teamId);
        }
        if ($destination->getAttribute('status') !== 'active' || ($destination->getAttribute('unlock_key') !== null && ! $this->hasUnlock($actorId, (string) $destination->getAttribute('unlock_key'), $tenantId, $teamId))) {
            throw ValidationException::withMessages(['destination' => 'The destination is not available.']);
        }
        if ($origin?->getKey() === $destination->getKey() || ($origin !== null && $origin->world_id !== $destination->world_id)) {
            throw ValidationException::withMessages(['destination' => 'Origin and destination must differ.']);
        }
        $travel = DB::transaction(function () use ($actorId, $tenantId, $teamId, $origin, $destination, $idempotencyKey, $metadata): WorldTravel {
            if ($idempotencyKey !== null && ($existing = WorldTravel::query()->where('actor_id', $actorId)->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if ($existing->tenant_id !== $tenantId || (string) $existing->team_id !== (string) $teamId || (string) $existing->origin_id !== (string) $origin?->getKey() || (string) $existing->destination_id !== (string) $destination->getKey()) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another travel operation.']);
                }

                return $existing;
            }

            return WorldTravel::query()->create(['id' => (string) Str::uuid(), 'actor_id' => $actorId, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'origin_id' => $origin?->getKey(), 'destination_id' => $destination->getKey(), 'idempotency_key' => $idempotencyKey, 'metadata' => $metadata, 'created_at' => now()]);
        });
        if ($travel->wasRecentlyCreated) {
            WorldTravelled::dispatch((string) $travel->getKey(), $actorId, (string) $destination->getKey());
        }

        return $travel;
    }

    public function grantUnlock(string $actorId, WorldEntity|string $entity, ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null, array $metadata = []): WorldUnlock
    {
        if (trim($actorId) === '') {
            throw ValidationException::withMessages(['actor' => 'An actor is required.']);
        }

        $worldEntity = $entity instanceof WorldEntity ? $entity : WorldEntity::query()->whereKey($entity)->firstOrFail();
        $this->assertScope($worldEntity, $tenantId, $teamId);
        $unlockKey = trim((string) $worldEntity->getAttribute('unlock_key'));
        if ($unlockKey === '') {
            throw ValidationException::withMessages(['entity' => 'The world entity does not define an unlock key.']);
        }

        $unlock = DB::transaction(function () use ($actorId, $worldEntity, $tenantId, $teamId, $idempotencyKey, $metadata, $unlockKey): WorldUnlock {
            if ($idempotencyKey !== null && ($existing = WorldUnlock::query()->where('actor_id', $actorId)->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if ($existing->unlock_key !== $unlockKey || $existing->tenant_id !== $tenantId || (string) $existing->team_id !== (string) $teamId) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another unlock operation.']);
                }

                return $existing;
            }

            $existing = WorldUnlock::query()->where('actor_id', $actorId)->where('unlock_key', $unlockKey)->where('status', 'granted')->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))->lockForUpdate()->first();
            if ($existing !== null) {
                if ($idempotencyKey !== null && $existing->idempotency_key === null) {
                    $existing->update(['idempotency_key' => $idempotencyKey]);
                }

                return $existing->refresh();
            }

            return WorldUnlock::query()->create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'team_id' => $teamId,
                'actor_id' => $actorId, 'entity_id' => $worldEntity->getKey(), 'unlock_key' => $unlockKey,
                'status' => 'granted', 'metadata' => $metadata, 'idempotency_key' => $idempotencyKey,
                'granted_at' => now(),
            ]);
        });

        if ($unlock->wasRecentlyCreated) {
            WorldUnlockGranted::dispatch((string) $unlock->getKey(), $actorId, $unlockKey);
        }

        return $unlock;
    }

    public function revokeUnlock(string $actorId, WorldUnlock|int|string $unlock, ?string $tenantId = null, ?string $teamId = null): WorldUnlock
    {
        $record = $unlock instanceof WorldUnlock ? $unlock : WorldUnlock::query()->whereKey($unlock)->firstOrFail();
        if ($record->actor_id !== $actorId || $record->tenant_id !== $tenantId || (string) $record->team_id !== (string) $teamId) {
            throw ValidationException::withMessages(['unlock' => 'The unlock is not available in the current context.']);
        }
        if ($record->status !== 'granted') {
            return $record;
        }

        $record->update(['status' => 'revoked', 'revoked_at' => now()]);
        $record = $record->refresh();
        WorldUnlockRevoked::dispatch((string) $record->getKey(), $actorId, $record->unlock_key);

        return $record;
    }

    public function hasUnlock(string $actorId, string $unlockKey, ?string $tenantId = null, ?string $teamId = null): bool
    {
        return WorldUnlock::query()->where('actor_id', $actorId)->where('unlock_key', $unlockKey)->where('status', 'granted')->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))->exists();
    }

    public function update(WorldEntity $entity, ?string $tenantId, ?string $teamId, string $name, string $slug, string $status, array $attributes = [], ?array $coordinates = null, ?string $unlockKey = null): WorldEntity
    {
        $this->assertScope($entity, $tenantId, $teamId);
        foreach (['name' => $name, 'slug' => $slug] as $field => $value) {
            if (trim($value) === '') {
                throw ValidationException::withMessages([$field => 'This value is required.']);
            }
        }
        if (! in_array($status, ['active', 'hidden', 'archived'], true)) {
            throw ValidationException::withMessages(['status' => 'World entity status is invalid.']);
        }

        $updated = DB::transaction(function () use ($entity, $tenantId, $teamId, $name, $slug, $status, $attributes, $coordinates, $unlockKey): WorldEntity {
            $current = WorldEntity::query()->whereKey($entity->getKey())->lockForUpdate()->firstOrFail();
            $this->assertScope($current, $tenantId, $teamId);
            $current->update(['name' => $name, 'slug' => $slug, 'status' => $status, 'attributes' => $attributes, 'coordinates' => $coordinates, 'unlock_key' => $unlockKey]);

            return $current->refresh();
        });
        WorldEntityUpdated::dispatch((string) $updated->getKey(), $updated->status);

        return $updated;
    }

    private function assertScope(WorldEntity $entity, ?string $tenantId, ?string $teamId): void
    {
        if (($entity->tenant_id !== null && $entity->tenant_id !== $tenantId) || ($entity->team_id !== null && (string) $entity->team_id !== (string) $teamId)) {
            throw ValidationException::withMessages(['world' => 'The world entity is not available in the current context.']);
        }
    }
}
