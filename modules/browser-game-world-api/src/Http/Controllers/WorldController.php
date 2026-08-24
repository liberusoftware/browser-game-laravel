<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\World\Models\WorldEntity;
use Liberu\BrowserGame\World\Queries\WorldQuery;

final class WorldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $entities = app(WorldQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $request->string('kind')->toString() ?: null)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $entities->through(fn (Model $entity, int $key): array => $this->resource($entity))]);
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
