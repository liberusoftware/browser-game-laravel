<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Competition\Models\CompetitionMatch;
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;
use Liberu\BrowserGame\Competition\Queries\CompetitionQuery;
use Liberu\BrowserGame\Competition\Support\CompetitionManager;

final class CompetitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(CompetitionQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (CompetitionRecord $item): array => $this->resource($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'kind' => ['nullable', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->resource(app(CompetitionManager::class)->create($v['name'], $v['kind'] ?? 'pvp', $v['data'] ?? [], null, $request->user()?->currentTeam?->getKey(), $v['idempotency_key'] ?? null))], 201);
    }

    public function queue(Request $request, CompetitionRecord $competition): JsonResponse
    {
        return response()->json(['data' => app(CompetitionManager::class)->queue($competition, (string) $request->user()->getAuthIdentifier())], 201);
    }

    public function match(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $v = $request->validate(['player_a' => ['required', 'string'], 'player_b' => ['required', 'string'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => app(CompetitionManager::class)->match($competition, $v['player_a'], $v['player_b'], $v['idempotency_key'] ?? null)], 201);
    }

    public function resolve(Request $request, CompetitionMatch $match): JsonResponse
    {
        $v = $request->validate(['winner_id' => ['required', 'string'], 'evidence' => ['array']]);

        return response()->json(['data' => app(CompetitionManager::class)->resolve($match, $v['winner_id'], $v['evidence'] ?? [])]);
    }

    public function show(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $competition = app(CompetitionQuery::class)->visible(null, (string) $teamId)
            ->whereKey($competition->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($competition)]);
    }

    private function resource(CompetitionRecord $competition): array
    {
        return ['id' => (string) $competition->getKey(), 'type' => 'browser-game-competition', 'attributes' => ['name' => $competition->getAttribute('name'), 'kind' => $competition->getAttribute('kind'), 'status' => $competition->getAttribute('status'), 'season' => $competition->getAttribute('season'), 'data' => $competition->getAttribute('data'), 'tenant_id' => $competition->getAttribute('tenant_id'), 'team_id' => $competition->getAttribute('team_id')]];
    }
}
