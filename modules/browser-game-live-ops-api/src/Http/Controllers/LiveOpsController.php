<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
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
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(LiveOpsQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (LiveOpsRecord $item): array => $this->resource($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'kind' => ['required', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $record = app(LiveOpsManager::class)->create($v['name'], $v['kind'], $v['data'] ?? [], null, $request->user()?->currentTeam?->getKey(), $v['idempotency_key'] ?? null);

        return response()->json(['data' => $this->resource($record)], 201);
    }

    public function publish(LiveOpsRecord $liveOps): JsonResponse
    {
        return response()->json(['data' => $this->resource(app(LiveOpsManager::class)->publish($liveOps))]);
    }

    public function claim(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $v = $request->validate(['claim_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => app(LiveOpsManager::class)->claim((string) $request->user()->getAuthIdentifier(), $liveOps, $v['claim_key'] ?? 'default')], 201);
    }

    public function dailyStatus(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        return response()->json(['data' => app(LiveOpsManager::class)->dailyStatus(
            (string) $request->user()->getAuthIdentifier(),
            $liveOps,
            $request->string('timezone')->toString() ?: null,
        )]);
    }

    public function dailyClaim(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $data = $request->validate(['timezone' => ['nullable', 'timezone']]);
        $claim = app(LiveOpsManager::class)->claimDaily(
            (string) $request->user()->getAuthIdentifier(),
            $liveOps,
            $data['timezone'] ?? null,
        );

        return response()->json(['data' => [
            'id' => (string) $claim->getKey(),
            'type' => 'browser-game-live-ops-claim',
            'attributes' => ['claim_key' => $claim->claim_key, 'status' => $claim->status, 'grant' => $claim->grant],
        ]], 201);
    }

    public function rollback(Request $request, LiveOpsRecord $liveOps): JsonResponse
    {
        $v = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => $this->resource(app(LiveOpsManager::class)->rollback($liveOps, (string) $request->user()->getAuthIdentifier(), $v['reason']))]);
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
