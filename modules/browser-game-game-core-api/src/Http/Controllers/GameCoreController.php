<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\GameCore\Contracts\GameCoreContext;
use Liberu\BrowserGame\GameCore\Models\GameWorld;
use Liberu\BrowserGame\GameCore\Queries\GameCoreOverview;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;

final class GameCoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $worlds = GameWorld::query()
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $context->tenantId()))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $context->teamId()))
            ->latest()
            ->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $worlds->through(fn (GameWorld $world): array => $this->resource($world))]);
    }

    public function show(Request $request, string $world): JsonResponse
    {
        $overview = app(GameCoreOverview::class)->forWorld($this->context($request), $world);

        return response()->json(['data' => $this->resource($overview['world'], $overview)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'slug' => ['required', 'string', 'max:120', 'alpha_dash'], 'metadata' => ['array']]);
        $world = app(GameCoreManager::class)->createWorld($this->context($request), $data['name'], $data['slug'], $data['metadata'] ?? []);

        return response()->json(['data' => $this->resource($world)], 201);
    }

    public function update(Request $request, GameWorld $world): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'status' => ['required', 'in:draft,active,archived'], 'metadata' => ['array']]);
        $updated = app(GameCoreManager::class)->updateWorld($this->context($request), $world, $data['name'], $data['status'], $data['metadata'] ?? []);

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function clock(Request $request, GameWorld $world): JsonResponse
    {
        $v = $request->validate(['current_at' => ['required', 'date'], 'speed' => ['required', 'numeric', 'min:0'], 'paused' => ['boolean']]);

        return response()->json(['data' => app(GameCoreManager::class)->setClock($this->context($request), $world, $v['current_at'], (string) $v['speed'], (bool) ($v['paused'] ?? false))]);
    }

    public function ruleset(Request $request, GameWorld $world): JsonResponse
    {
        $v = $request->validate(['version' => ['required', 'integer', 'min:1'], 'rules' => ['required', 'array']]);

        return response()->json(['data' => app(GameCoreManager::class)->publishRuleset($this->context($request), $world, $v['version'], $v['rules'])], 201);
    }

    public function content(Request $request, GameWorld $world): JsonResponse
    {
        $v = $request->validate(['version' => ['required', 'integer', 'min:1'], 'content_hash' => ['required', 'string', 'max:128'], 'manifest' => ['required', 'array']]);

        return response()->json(['data' => app(GameCoreManager::class)->publishContentVersion($this->context($request), $world, $v['version'], $v['content_hash'], $v['manifest'])], 201);
    }

    public function flag(Request $request, GameWorld $world, string $key): JsonResponse
    {
        $v = $request->validate(['enabled' => ['required', 'boolean'], 'rollout_percentage' => ['integer', 'min:0', 'max:100'], 'constraints' => ['array']]);

        return response()->json(['data' => app(GameCoreManager::class)->setFeatureFlag($this->context($request), $world, $key, $v['enabled'], $v['rollout_percentage'] ?? 100, $v['constraints'] ?? [])]);
    }

    public function maintenance(Request $request, GameWorld $world): JsonResponse
    {
        $v = $request->validate(['status' => ['required', 'in:scheduled,active,resolved'], 'message' => ['nullable', 'string', 'max:2000']]);

        return response()->json(['data' => app(GameCoreManager::class)->setMaintenance($this->context($request), $world, $v['status'], $v['message'] ?? null)]);
    }

    private function context(Request $request): GameCoreContext
    {
        $user = $request->user();
        $team = method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return new ArrayGameCoreContext(
            actor: $user?->getAuthIdentifier() === null ? null : (string) $user->getAuthIdentifier(),
            tenant: $team?->getAttribute('tenant_id') === null ? null : (string) $team->getAttribute('tenant_id'),
            team: $team?->getKey() === null ? null : (string) $team->getKey(),
        );
    }

    private function resource(GameWorld $world, ?array $overview = null): array
    {
        return ['id' => $world->getKey(), 'type' => 'browser-game-game-core', 'attributes' => [
            'name' => $world->name, 'slug' => $world->slug, 'status' => $world->status,
            'metadata' => $world->metadata, 'overview' => $overview,
            'created_at' => $world->created_at?->toISOString(), 'updated_at' => $world->updated_at?->toISOString(),
        ]];
    }
}
