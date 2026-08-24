<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\ModerationAndAnalytics\Models\ModerationAndAnalyticsRecord;
use Liberu\BrowserGame\ModerationAndAnalytics\Queries\ModerationAndAnalyticsQuery;
use Liberu\BrowserGame\ModerationAndAnalytics\Support\ModerationAndAnalyticsManager;

final class ModerationAndAnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(ModerationAndAnalyticsQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (ModerationAndAnalyticsRecord $item): array => $this->resource($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['kind' => ['required', 'string'], 'name' => ['required', 'string', 'max:255'], 'target_id' => ['nullable', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $record = app(ModerationAndAnalyticsManager::class)->record($v['kind'], $v['name'], (string) $request->user()->getAuthIdentifier(), $v['target_id'] ?? null, $v['data'] ?? [], null, $request->user()?->currentTeam?->getKey(), $v['idempotency_key'] ?? null);

        return response()->json(['data' => $this->resource($record)], 201);
    }

    public function resolve(Request $request, ModerationAndAnalyticsRecord $moderationAndAnalytics): JsonResponse
    {
        $v = $request->validate(['status' => ['nullable', 'in:resolved,dismissed,active,revoked']]);

        return response()->json(['data' => $this->resource(app(ModerationAndAnalyticsManager::class)->resolve($moderationAndAnalytics, $v['status'] ?? 'resolved'))]);
    }

    public function show(Request $request, ModerationAndAnalyticsRecord $moderationAndAnalytics): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $moderationAndAnalytics = app(ModerationAndAnalyticsQuery::class)->visible(null, (string) $teamId)
            ->whereKey($moderationAndAnalytics->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($moderationAndAnalytics)]);
    }

    private function resource(Model $moderationAndAnalytics): array
    {
        return ['id' => (string) $moderationAndAnalytics->getKey(), 'type' => 'browser-game-moderation-and-analytics', 'attributes' => ['name' => $moderationAndAnalytics->getAttribute('name'), 'kind' => $moderationAndAnalytics->getAttribute('kind'), 'status' => $moderationAndAnalytics->getAttribute('status'), 'actor_id' => $moderationAndAnalytics->getAttribute('actor_id'), 'target_id' => $moderationAndAnalytics->getAttribute('target_id'), 'severity' => $moderationAndAnalytics->getAttribute('severity'), 'value' => $moderationAndAnalytics->getAttribute('value'), 'data' => $moderationAndAnalytics->getAttribute('data'), 'tenant_id' => $moderationAndAnalytics->getAttribute('tenant_id'), 'team_id' => $moderationAndAnalytics->getAttribute('team_id')]];
    }
}
