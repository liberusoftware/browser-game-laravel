<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Combat\Models\CombatBattle;
use Liberu\BrowserGame\Combat\Queries\CombatQuery;
use Liberu\BrowserGame\Combat\Support\CombatManager;

final class CombatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $this->team($request);
        $items = app(CombatQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $battle, int $key): array => $this->resource($battle))]);
    }

    public function show(Request $request, CombatBattle $battle): JsonResponse
    {
        $team = $this->team($request);
        abort_unless(app(CombatQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($battle->getKey())->exists(), 404);

        return response()->json(['data' => $this->resource($battle)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $battle = app(CombatManager::class)->start((string) $user->getKey(), (string) $request->string('opponent_id'), null, null, $request->header('Idempotency-Key'), (array) $request->input('state', []));

        return response()->json(['data' => $this->resource($battle)], 201);
    }

    public function action(Request $request, CombatBattle $battle): JsonResponse
    {
        $validated = $request->validate(['action' => ['required', 'string', 'max:120'], 'value' => ['nullable', 'integer', 'min:0'], 'effects' => ['array']]);
        $action = app(CombatManager::class)->resolve($battle, (string) $request->user()->getKey(), $validated['action'], $validated['value'] ?? 0, $request->header('Idempotency-Key'), $validated['effects'] ?? []);
        $battle = $battle->fresh();

        return response()->json(['data' => ['id' => (string) $action->getKey(), 'type' => 'browser-game-combat-action', 'attributes' => ['combat_id' => $battle->getKey(), 'turn' => $action->getAttribute('turn'), 'action' => $action->getAttribute('action'), 'value' => $action->getAttribute('value'), 'effects' => $action->getAttribute('effects'), 'battle_status' => $battle->status, 'state' => $battle->state]]]);
    }

    public function definition(Request $request): JsonResponse
    {
        $v = $request->validate(['kind' => ['required', 'string'], 'slug' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'max:255'], 'effects' => ['array'], 'data' => ['array'], 'cooldown' => ['integer', 'min:0']]);

        return response()->json(['data' => app(CombatManager::class)->define($v['kind'], $v['slug'], $v['name'], $v['effects'] ?? [], $v['data'] ?? [], $v['cooldown'] ?? 0)], 201);
    }

    public function simulate(Request $request): JsonResponse
    {
        $v = $request->validate(['opponent_id' => ['required', 'string'], 'actions' => ['required', 'array'], 'state' => ['array']]);

        return response()->json(['data' => app(CombatManager::class)->simulate((string) $request->user()->getAuthIdentifier(), $v['opponent_id'], $v['actions'], $v['state'] ?? [])]);
    }

    private function team(Request $request): mixed
    {
        $user = $request->user();

        return method_exists($user, 'currentTeam') ? $user->currentTeam : null;
    }

    private function resource(Model $battle): array
    {
        return ['id' => (string) $battle->getKey(), 'type' => 'browser-game-combat', 'attributes' => ['actor_id' => $battle->getAttribute('actor_id'), 'opponent_id' => $battle->getAttribute('opponent_id'), 'status' => $battle->getAttribute('status'), 'turn' => $battle->getAttribute('turn'), 'state' => $battle->getAttribute('state'), 'created_at' => $battle->getAttribute('created_at')?->toISOString()]];
    }
}
