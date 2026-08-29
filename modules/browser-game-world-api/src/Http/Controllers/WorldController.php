<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\World\Models\WorldEntity;
use Liberu\BrowserGame\World\Models\WorldUnlock;
use Liberu\BrowserGame\World\Queries\WorldQuery;
use Liberu\BrowserGame\World\Support\WorldManager;

final class WorldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $entities = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $request->string('kind')->toString() ?: null)->latest()->paginate($pageSize);

        return response()->json($entities->through(fn (WorldEntity $entity): array => $this->resource($entity)));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $v = $request->validate(['kind' => ['required', 'string'], 'name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:120', 'alpha_dash'], 'attributes' => ['array'], 'world_id' => ['nullable', 'uuid'], 'unlock_key' => ['nullable', 'string']]);

        return response()->json(['data' => $this->resource(app(WorldManager::class)->define($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['kind'], $v['name'], $v['slug'], $v['attributes'] ?? [], $v['world_id'] ?? null, $v['unlock_key'] ?? null))], 201);
    }

    public function travel(Request $request): JsonResponse
    {
        $v = $request->validate(['destination_id' => ['required', 'uuid'], 'origin_id' => ['nullable', 'uuid'], 'idempotency_key' => ['nullable', 'string', 'max:128'], 'metadata' => ['array']]);
        $user = $request->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $query = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $destination = (clone $query)->whereKey($v['destination_id'])->firstOrFail();
        $origin = isset($v['origin_id']) ? (clone $query)->whereKey($v['origin_id'])->firstOrFail() : null;
        $travel = app(WorldManager::class)->travel((string) $request->user()->getAuthIdentifier(), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $origin, $destination, $v['idempotency_key'] ?? null, $v['metadata'] ?? []);

        return response()->json(['data' => ['id' => (string) $travel->getKey(), 'type' => 'browser-game-world-travel', 'attributes' => ['actor_id' => $travel->actor_id, 'origin_id' => $travel->origin_id, 'destination_id' => $travel->destination_id, 'metadata' => $travel->metadata]]], 201);
    }

    public function unlock(Request $request, WorldEntity $entity): JsonResponse
    {
        $user = $request->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $scope = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($entity->getKey())->firstOrFail();
        $v = $request->validate(['idempotency_key' => ['nullable', 'string', 'max:128'], 'metadata' => ['array']]);
        $unlock = app(WorldManager::class)->grantUnlock((string) $request->user()->getAuthIdentifier(), $scope, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['idempotency_key'] ?? null, $v['metadata'] ?? []);

        return response()->json(['data' => $this->unlockResource($unlock)], 201);
    }

    public function revokeUnlock(Request $request, WorldUnlock $unlock): JsonResponse
    {
        $user = $request->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $updated = app(WorldManager::class)->revokeUnlock((string) $request->user()->getAuthIdentifier(), $unlock, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->unlockResource($updated)]);
    }

    public function show(Request $request, WorldEntity $entity): JsonResponse
    {
        $user = $request->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        abort_unless(app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($entity->getKey())->exists(), 404);

        return response()->json(['data' => $this->resource($entity)]);
    }

    public function update(Request $request, WorldEntity $entity): JsonResponse
    {
        $user = $request->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $entity = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($entity->getKey())->firstOrFail();
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:120', 'alpha_dash'], 'status' => ['required', 'in:active,hidden,archived'], 'attributes' => ['array'], 'coordinates' => ['nullable', 'array'], 'unlock_key' => ['nullable', 'string']]);
        $updated = app(WorldManager::class)->update($entity, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['name'], $v['slug'], $v['status'], $v['attributes'] ?? [], $v['coordinates'] ?? null, $v['unlock_key'] ?? null);

        return response()->json(['data' => $this->resource($updated)]);
    }

    private function resource(WorldEntity $entity): array
    {
        return ['id' => (string) $entity->getKey(), 'type' => 'browser-game-world', 'attributes' => ['kind' => $entity->kind, 'name' => $entity->name, 'slug' => $entity->slug, 'status' => $entity->status, 'attributes' => $entity->attributes, 'coordinates' => $entity->coordinates, 'unlock_key' => $entity->unlock_key, 'created_at' => $entity->created_at?->toISOString(), 'updated_at' => $entity->updated_at?->toISOString()]];
    }

    private function unlockResource(WorldUnlock $unlock): array
    {
        return ['id' => (string) $unlock->getKey(), 'type' => 'browser-game-world-unlock', 'attributes' => ['actor_id' => $unlock->actor_id, 'entity_id' => $unlock->entity_id, 'unlock_key' => $unlock->unlock_key, 'status' => $unlock->status, 'metadata' => $unlock->metadata, 'granted_at' => $unlock->granted_at?->toISOString(), 'revoked_at' => $unlock->revoked_at?->toISOString()]];
    }
}
