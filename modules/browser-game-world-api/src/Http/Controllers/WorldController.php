<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\World\Models\WorldEntity;
use Liberu\BrowserGame\World\Queries\WorldQuery;
use Liberu\BrowserGame\World\Support\WorldManager;

final class WorldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $entities = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $request->string('kind')->toString() ?: null)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $entities->through(fn (WorldEntity $entity): array => $this->resource($entity))]);
    }

    public function store(Request $request): JsonResponse
    {
        $team = method_exists($request->user(), 'currentTeam') ? $request->user()->currentTeam : null;
        $v = $request->validate(['kind' => ['required', 'string'], 'name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:120', 'alpha_dash'], 'attributes' => ['array'], 'world_id' => ['nullable', 'uuid'], 'unlock_key' => ['nullable', 'string']]);

        return response()->json(['data' => $this->resource(app(WorldManager::class)->define($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['kind'], $v['name'], $v['slug'], $v['attributes'] ?? [], $v['world_id'] ?? null, $v['unlock_key'] ?? null))], 201);
    }

    public function travel(Request $request): JsonResponse
    {
        $v = $request->validate(['destination_id' => ['required', 'uuid'], 'origin_id' => ['nullable', 'uuid'], 'idempotency_key' => ['nullable', 'string', 'max:128'], 'metadata' => ['array']]);
        $destination = WorldEntity::query()->findOrFail($v['destination_id']);
        $origin = isset($v['origin_id']) ? WorldEntity::query()->find($v['origin_id']) : null;
        $team = method_exists($request->user(), 'currentTeam') ? $request->user()->currentTeam : null;
        $travel = app(WorldManager::class)->travel((string) $request->user()->getAuthIdentifier(), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $origin, $destination, $v['idempotency_key'] ?? null, $v['metadata'] ?? []);

        return response()->json(['data' => $travel], 201);
    }

    public function show(Request $request, WorldEntity $entity): JsonResponse
    {
        $team = method_exists($request->user(), 'currentTeam') ? $request->user()->currentTeam : null;
        abort_unless(app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($entity->getKey())->exists(), 404);

        return response()->json(['data' => $this->resource($entity)]);
    }

    private function resource(Model $entity): array
    {
        return ['id' => (string) $entity->getKey(), 'type' => 'browser-game-world', 'attributes' => ['kind' => $entity->getAttribute('kind'), 'name' => $entity->getAttribute('name'), 'slug' => $entity->getAttribute('slug'), 'status' => $entity->getAttribute('status'), 'attributes' => $entity->getAttribute('attributes'), 'coordinates' => $entity->getAttribute('coordinates'), 'unlock_key' => $entity->getAttribute('unlock_key')]];
    }
}
