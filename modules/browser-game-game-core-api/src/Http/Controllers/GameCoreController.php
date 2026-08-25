<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\GameCore\Contracts\GameCoreContext;
use Liberu\BrowserGame\GameCore\Models\GameClock;
use Liberu\BrowserGame\GameCore\Models\GameContentVersion;
use Liberu\BrowserGame\GameCore\Models\GameFeatureFlag;
use Liberu\BrowserGame\GameCore\Models\GameMaintenanceState;
use Liberu\BrowserGame\GameCore\Models\GameRuleset;
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
            ->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

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

        return response()->json(['data' => $this->clockResource(app(GameCoreManager::class)->setClock($this->context($request), $world, $v['current_at'], (string) $v['speed'], (bool) ($v['paused'] ?? false)))]);
    }

    public function ruleset(Request $request, GameWorld $world): JsonResponse
    {
        $v = $request->validate(['version' => ['required', 'integer', 'min:1'], 'rules' => ['required', 'array']]);

        return response()->json(['data' => $this->rulesetResource(app(GameCoreManager::class)->publishRuleset($this->context($request), $world, $v['version'], $v['rules']))], 201);
    }

    public function content(Request $request, GameWorld $world): JsonResponse
    {
        $v = $request->validate(['version' => ['required', 'integer', 'min:1'], 'content_hash' => ['required', 'string', 'max:128'], 'manifest' => ['required', 'array']]);

        return response()->json(['data' => $this->contentResource(app(GameCoreManager::class)->publishContentVersion($this->context($request), $world, $v['version'], $v['content_hash'], $v['manifest']))], 201);
    }

    public function flag(Request $request, GameWorld $world, string $key): JsonResponse
    {
        $v = $request->validate(['enabled' => ['required', 'boolean'], 'rollout_percentage' => ['integer', 'min:0', 'max:100'], 'constraints' => ['array']]);

        return response()->json(['data' => $this->flagResource(app(GameCoreManager::class)->setFeatureFlag($this->context($request), $world, $key, $v['enabled'], $v['rollout_percentage'] ?? 100, $v['constraints'] ?? []))]);
    }

    public function evaluateFlag(Request $request, GameWorld $world, string $key): JsonResponse
    {
        $attributes = $request->validate(['attributes' => ['nullable', 'array']])['attributes'] ?? [];
        $context = $this->context($request);
        abort_unless($this->worldAvailable($world, $context), 404);
        app(GameCoreOverview::class)->forWorld($context, (string) $world->getKey());

        return response()->json(['data' => [
            'type' => 'browser-game-game-feature-evaluation',
            'attributes' => ['world_id' => (string) $world->getKey(), 'key' => $key, 'enabled' => app(GameCoreOverview::class)->isEnabled($context, $world, $key, $attributes)],
        ]]);
    }

    public function maintenance(Request $request, GameWorld $world): JsonResponse
    {
        $v = $request->validate(['status' => ['required', 'in:scheduled,active,resolved'], 'message' => ['nullable', 'string', 'max:2000']]);

        return response()->json(['data' => $this->maintenanceResource(app(GameCoreManager::class)->setMaintenance($this->context($request), $world, $v['status'], $v['message'] ?? null))]);
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
            'metadata' => $world->metadata, 'overview' => $overview === null ? null : [
                'clock' => $overview['clock'] instanceof GameClock ? $this->clockResource($overview['clock']) : null,
                'ruleset' => $overview['ruleset'] instanceof GameRuleset ? $this->rulesetResource($overview['ruleset']) : null,
                'content_version' => $overview['content_version'] instanceof GameContentVersion ? $this->contentResource($overview['content_version']) : null,
                'maintenance' => $overview['maintenance'] instanceof GameMaintenanceState ? $this->maintenanceResource($overview['maintenance']) : null,
                'feature_flags' => collect($overview['feature_flags'])->map(fn (GameFeatureFlag $flag): array => $this->flagResource($flag))->values()->all(),
            ],
            'created_at' => $world->created_at?->toISOString(), 'updated_at' => $world->updated_at?->toISOString(),
        ]];
    }

    private function clockResource(GameClock $clock): array
    {
        return ['id' => (string) $clock->getKey(), 'type' => 'browser-game-game-clock', 'attributes' => ['world_id' => (string) $clock->world_id, 'current_at' => $clock->current_at?->toISOString(), 'speed' => (string) $clock->speed, 'paused' => (bool) $clock->paused, 'updated_by' => $clock->updated_by, 'created_at' => $clock->created_at?->toISOString(), 'updated_at' => $clock->updated_at?->toISOString()]];
    }

    private function rulesetResource(GameRuleset $ruleset): array
    {
        return ['id' => (string) $ruleset->getKey(), 'type' => 'browser-game-game-ruleset', 'attributes' => ['world_id' => (string) $ruleset->world_id, 'version' => (int) $ruleset->version, 'status' => $ruleset->status, 'rules' => $ruleset->rules, 'published_at' => $ruleset->published_at?->toISOString(), 'published_by' => $ruleset->published_by, 'created_at' => $ruleset->created_at?->toISOString(), 'updated_at' => $ruleset->updated_at?->toISOString()]];
    }

    private function contentResource(GameContentVersion $content): array
    {
        return ['id' => (string) $content->getKey(), 'type' => 'browser-game-game-content-version', 'attributes' => ['world_id' => (string) $content->world_id, 'version' => (int) $content->version, 'status' => $content->status, 'content_hash' => $content->content_hash, 'manifest' => $content->manifest, 'published_at' => $content->published_at?->toISOString(), 'published_by' => $content->published_by, 'created_at' => $content->created_at?->toISOString(), 'updated_at' => $content->updated_at?->toISOString()]];
    }

    private function flagResource(GameFeatureFlag $flag): array
    {
        return ['id' => (string) $flag->getKey(), 'type' => 'browser-game-game-feature-flag', 'attributes' => ['world_id' => $flag->world_id, 'key' => $flag->key, 'enabled' => (bool) $flag->enabled, 'rollout_percentage' => (int) $flag->rollout_percentage, 'constraints' => $flag->constraints, 'changed_by' => $flag->changed_by, 'created_at' => $flag->created_at?->toISOString(), 'updated_at' => $flag->updated_at?->toISOString()]];
    }

    private function maintenanceResource(GameMaintenanceState $maintenance): array
    {
        return ['id' => (string) $maintenance->getKey(), 'type' => 'browser-game-game-maintenance-state', 'attributes' => ['world_id' => (string) $maintenance->world_id, 'status' => $maintenance->status, 'message' => $maintenance->message, 'starts_at' => $maintenance->starts_at?->toISOString(), 'ends_at' => $maintenance->ends_at?->toISOString(), 'changed_by' => $maintenance->changed_by, 'created_at' => $maintenance->created_at?->toISOString(), 'updated_at' => $maintenance->updated_at?->toISOString()]];
    }

    private function worldAvailable(GameWorld $world, GameCoreContext $context): bool
    {
        return ($world->tenant_id === null || (string) $world->tenant_id === (string) $context->tenantId())
            && ($world->team_id === null || (string) $world->team_id === (string) $context->teamId());
    }
}
