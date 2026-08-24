<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\LiveOps\Models\LiveOpsRecord;
use Liberu\BrowserGame\LiveOps\Queries\LiveOpsQuery;

final class LiveOpsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(LiveOpsQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $liveOps = app(LiveOpsQuery::class)->visible(null, (string) $teamId)
            ->whereKey($liveOps->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($liveOps)]);
    }

    private function resource(Model $liveOps): array
    {
        return ['id' => (string) $liveOps->getKey(), 'type' => 'browser-game-live-ops', 'attributes' => ['name' => $liveOps->getAttribute('name'), 'status' => $liveOps->getAttribute('status'), 'data' => $liveOps->getAttribute('data'), 'tenant_id' => $liveOps->getAttribute('tenant_id'), 'team_id' => $liveOps->getAttribute('team_id')]];
    }
}
