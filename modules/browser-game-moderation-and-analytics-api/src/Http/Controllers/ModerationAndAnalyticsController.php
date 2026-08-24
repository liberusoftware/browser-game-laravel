<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\ModerationAndAnalytics\Models\ModerationAndAnalyticsRecord;
use Liberu\BrowserGame\ModerationAndAnalytics\Queries\ModerationAndAnalyticsQuery;

final class ModerationAndAnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(ModerationAndAnalyticsQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(ModerationAndAnalyticsRecord $moderationAndAnalytics): JsonResponse
    {
        return response()->json(['data' => $this->resource($moderationAndAnalytics)]);
    }

    private function resource(Model $moderationAndAnalytics): array
    {
        return ['id' => (string) $moderationAndAnalytics->getKey(), 'type' => 'browser-game-moderation-and-analytics', 'attributes' => ['name' => $moderationAndAnalytics->getAttribute('name'), 'status' => $moderationAndAnalytics->getAttribute('status'), 'data' => $moderationAndAnalytics->getAttribute('data'), 'tenant_id' => $moderationAndAnalytics->getAttribute('tenant_id'), 'team_id' => $moderationAndAnalytics->getAttribute('team_id')]];
    }
}
