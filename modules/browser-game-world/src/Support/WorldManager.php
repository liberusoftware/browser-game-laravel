<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\World\Events\WorldEntityDefined;
use Liberu\BrowserGame\World\Events\WorldTravelled;
use Liberu\BrowserGame\World\Models\WorldEntity;
use Liberu\BrowserGame\World\Models\WorldTravel;

final class WorldManager
{
    public function define(?string $tenantId, ?string $teamId, string $kind, string $name, string $slug, array $attributes = [], ?string $worldId = null, ?string $unlockKey = null): WorldEntity
    {
        if (! in_array($kind, (array) config('browser-game.world.kinds'), true)) {
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
        if ($destination->getAttribute('status') !== 'active' || ($destination->getAttribute('unlock_key') !== null && ! (bool) ($metadata['unlocked'] ?? false))) {
            throw ValidationException::withMessages(['destination' => 'The destination is not available.']);
        }
        if ($origin?->getKey() === $destination->getKey()) {
            throw ValidationException::withMessages(['destination' => 'Origin and destination must differ.']);
        }
        $travel = DB::transaction(fn (): WorldTravel => WorldTravel::query()->firstOrCreate(
            ['actor_id' => $actorId, 'idempotency_key' => $idempotencyKey],
            ['id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'team_id' => $teamId, 'origin_id' => $origin?->getKey(), 'destination_id' => $destination->getKey(), 'metadata' => $metadata, 'created_at' => now()],
        ));
        WorldTravelled::dispatch((string) $travel->getKey(), $actorId, (string) $destination->getKey());

        return $travel;
    }
}
