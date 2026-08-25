<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\World\Events\WorldEntityDefined;
use Liberu\BrowserGame\World\Events\WorldEntityUpdated;
use Liberu\BrowserGame\World\Events\WorldTravelled;
use Liberu\BrowserGame\World\Models\WorldEntity;
use Liberu\BrowserGame\World\Models\WorldTravel;

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
        if ($destination->getAttribute('status') !== 'active' || ($destination->getAttribute('unlock_key') !== null && ! (bool) ($metadata['unlocked'] ?? false))) {
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
