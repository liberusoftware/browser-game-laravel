<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Competition\Models\CompetitionMatch;
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;
use Liberu\BrowserGame\Competition\Models\CompetitionReward;
use Liberu\BrowserGame\Competition\Queries\CompetitionQuery;
use Liberu\BrowserGame\Competition\Support\CompetitionManager;

final class CompetitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $items = app(CompetitionQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate($pageSize);

        return response()->json($items->through(fn (CompetitionRecord $item): array => $this->resource($item)));
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'kind' => ['nullable', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        $team = $request->user()?->currentTeam;

        return response()->json(['data' => $this->resource(app(CompetitionManager::class)->create($v['name'], $v['kind'] ?? 'pvp', $v['data'] ?? [], $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['idempotency_key'] ?? null))], 201);
    }

    public function queue(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $competition = $this->authorizedCompetition($request, $competition);

        return response()->json(['data' => $this->entryResource(app(CompetitionManager::class)->queue($competition, (string) $request->user()->getAuthIdentifier()))], 201);
    }

    public function match(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $competition = $this->authorizedCompetition($request, $competition);
        $v = $request->validate(['player_a' => ['required', 'string'], 'player_b' => ['required', 'string'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->matchResource(app(CompetitionManager::class)->match($competition, $v['player_a'], $v['player_b'], $v['idempotency_key'] ?? null))], 201);
    }

    public function resolve(Request $request, CompetitionMatch $match): JsonResponse
    {
        $this->authorizedCompetition($request, $match->competition()->firstOrFail());
        $v = $request->validate(['winner_id' => ['required', 'string'], 'evidence' => ['array']]);

        return response()->json(['data' => $this->matchResource(app(CompetitionManager::class)->resolve($match, $v['winner_id'], $v['evidence'] ?? []))]);
    }

    public function leaderboard(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $competition = $this->authorizedCompetition($request, $competition);

        return response()->json(['data' => app(CompetitionManager::class)->leaderboard($competition, min(max($request->integer('limit', 100), 1), 100))->map(fn ($entry): array => $this->entryResource($entry))->values()]);
    }

    public function rewards(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $competition = $this->authorizedCompetition($request, $competition);
        $rewards = $competition->rewards()->where('actor_id', (string) $request->user()->getAuthIdentifier())->latest()->get();

        return response()->json(['data' => $rewards->map(fn (CompetitionReward $reward): array => $this->rewardResource($reward))->values()]);
    }

    public function claimReward(Request $request, CompetitionReward $reward): JsonResponse
    {
        $this->authorizedCompetition($request, $reward->competition()->firstOrFail());

        return response()->json(['data' => $this->rewardResource(app(CompetitionManager::class)->claimReward((string) $request->user()->getAuthIdentifier(), $reward))]);
    }

    public function flag(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $competition = $this->authorizedCompetition($request, $competition);
        $v = $request->validate(['reason' => ['required', 'string', 'max:1000'], 'match_id' => ['nullable', 'uuid']]);
        $match = $v['match_id'] === null ? null : CompetitionMatch::query()->where('competition_id', $competition->getKey())->whereKey($v['match_id'])->firstOrFail();

        return response()->json(['data' => $this->flagResource(app(CompetitionManager::class)->flagCollusion($competition, (string) $request->user()->getAuthIdentifier(), $v['reason'], $match))], 201);
    }

    public function show(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $competition = $this->authorizedCompetition($request, $competition);

        return response()->json(['data' => $this->resource($competition)]);
    }

    private function resource(CompetitionRecord $competition): array
    {
        return ['id' => (string) $competition->getKey(), 'type' => 'browser-game-competition', 'attributes' => ['name' => $competition->name, 'kind' => $competition->kind, 'status' => $competition->status, 'season' => $competition->season, 'data' => $competition->data, 'tenant_id' => $competition->tenant_id, 'team_id' => $competition->team_id, 'starts_at' => $competition->starts_at?->toISOString(), 'ends_at' => $competition->ends_at?->toISOString(), 'created_at' => $competition->created_at?->toISOString(), 'updated_at' => $competition->updated_at?->toISOString()]];
    }

    private function entryResource(object $entry): array
    {
        return ['id' => (string) $entry->getKey(), 'type' => 'browser-game-competition-entry', 'attributes' => ['competition_id' => (string) $entry->competition_id, 'actor_id' => (string) $entry->actor_id, 'status' => $entry->status, 'rating' => $entry->rating, 'wins' => $entry->wins, 'losses' => $entry->losses, 'points' => $entry->points]];
    }

    private function matchResource(CompetitionMatch $match): array
    {
        return ['id' => (string) $match->getKey(), 'type' => 'browser-game-competition-match', 'attributes' => ['competition_id' => (string) $match->competition_id, 'player_a' => $match->player_a, 'player_b' => $match->player_b, 'status' => $match->status, 'winner_id' => $match->winner_id, 'evidence' => $match->evidence, 'created_at' => $match->created_at?->toISOString(), 'updated_at' => $match->updated_at?->toISOString()]];
    }

    private function rewardResource(CompetitionReward $reward): array
    {
        return ['id' => (string) $reward->getKey(), 'type' => 'browser-game-competition-reward', 'attributes' => ['competition_id' => (string) $reward->competition_id, 'actor_id' => (string) $reward->actor_id, 'reward_key' => $reward->reward_key, 'quantity' => $reward->quantity, 'claimed_at' => $reward->claimed_at?->toISOString(), 'data' => $reward->data]];
    }

    private function flagResource(object $flag): array
    {
        return ['id' => (string) $flag->getKey(), 'type' => 'browser-game-competition-flag', 'attributes' => ['competition_id' => (string) $flag->competition_id, 'match_id' => $flag->match_id, 'actor_id' => $flag->actor_id, 'reason' => $flag->reason, 'status' => $flag->status]];
    }

    private function authorizedCompetition(Request $request, CompetitionRecord $competition): CompetitionRecord
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        return app(CompetitionQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())->whereKey($competition->getKey())->firstOrFail();
    }
}
