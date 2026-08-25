<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\LiveOps\Models\LiveOpsRecord;
use Liberu\BrowserGame\LiveOps\Queries\LiveOpsQuery;
use Liberu\BrowserGame\LiveOps\Support\LiveOpsManager;

final class LiveOpsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $items = app(LiveOpsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate($pageSize);

        return response()->json(['data' => $items->through(fn (LiveOpsRecord $item): array => $this->resource($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'kind' => ['required', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $team = $request->user()?->currentTeam;
        $record = app(LiveOpsManager::class)->create($v['name'], $v['kind'], $v['data'] ?? [], $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['idempotency_key'] ?? null);

        return response()->json(['data' => $this->resource($record)], 201);
    }

    public function publish(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $liveOps = $this->authorizedLiveOps($request, $liveOps);

        return response()->json(['data' => $this->resource(app(LiveOpsManager::class)->publish($liveOps))]);
    }

    public function claim(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $liveOps = $this->authorizedLiveOps($request, $liveOps);
        $v = $request->validate(['claim_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->claimResource(app(LiveOpsManager::class)->claim((string) $request->user()->getAuthIdentifier(), $liveOps, $v['claim_key'] ?? 'default'))], 201);
    }

    public function dailyStatus(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $liveOps = $this->authorizedLiveOps($request, $liveOps);

        return response()->json(['data' => app(LiveOpsManager::class)->dailyStatus(
            (string) $request->user()->getAuthIdentifier(),
            $liveOps,
            $request->string('timezone')->toString() ?: null,
        )]);
    }

    public function dailyClaim(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $liveOps = $this->authorizedLiveOps($request, $liveOps);
        $data = $request->validate(['timezone' => ['nullable', 'timezone']]);
        $claim = app(LiveOpsManager::class)->claimDaily(
            (string) $request->user()->getAuthIdentifier(),
            $liveOps,
            $data['timezone'] ?? null,
        );

        return response()->json(['data' => $this->claimResource($claim)], 201);
    }

    public function rollback(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $liveOps = $this->authorizedLiveOps($request, $liveOps);
        $v = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => $this->resource(app(LiveOpsManager::class)->rollback($liveOps, (string) $request->user()->getAuthIdentifier(), $v['reason']))]);
    }

    public function show(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $liveOps = $this->authorizedLiveOps($request, $liveOps);

        return response()->json(['data' => $this->resource($liveOps)]);
    }

    private function resource(LiveOpsRecord $liveOps): array
    {
        return ['id' => (string) $liveOps->getKey(), 'type' => 'browser-game-live-ops', 'attributes' => ['name' => $liveOps->name, 'kind' => $liveOps->kind, 'status' => $liveOps->status, 'version' => $liveOps->version, 'starts_at' => $liveOps->starts_at?->toISOString(), 'ends_at' => $liveOps->ends_at?->toISOString(), 'data' => $liveOps->data, 'tenant_id' => $liveOps->tenant_id, 'team_id' => $liveOps->team_id, 'created_at' => $liveOps->created_at?->toISOString(), 'updated_at' => $liveOps->updated_at?->toISOString()]];
    }

    private function claimResource(object $claim): array
    {
        return ['id' => (string) $claim->getKey(), 'type' => 'browser-game-live-ops-claim', 'attributes' => ['live_ops_id' => (string) $claim->live_ops_id, 'claim_key' => $claim->claim_key, 'status' => $claim->status, 'grant' => $claim->grant, 'created_at' => $claim->created_at?->toISOString(), 'updated_at' => $claim->updated_at?->toISOString()]];
    }

    private function authorizedLiveOps(Request $request, LiveOpsRecord $record): LiveOpsRecord
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        return app(LiveOpsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())->whereKey($record->getKey())->firstOrFail();
    }
}
