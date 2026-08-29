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
        $team = $request->user()?->currentTeam;
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $items = app(ModerationAndAnalyticsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate($pageSize);

        return response()->json($items->through(fn (ModerationAndAnalyticsRecord $item): array => $this->resource($item)));
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['kind' => ['required', 'string'], 'name' => ['required', 'string', 'max:255'], 'target_id' => ['nullable', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $team = $request->user()?->currentTeam;
        $record = app(ModerationAndAnalyticsManager::class)->record($v['kind'], $v['name'], (string) $request->user()->getAuthIdentifier(), $v['target_id'] ?? null, $v['data'] ?? [], $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['idempotency_key'] ?? null);

        return response()->json(['data' => $this->resource($record)], 201);
    }

    public function report(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'report', true);
    }

    public function sanction(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'sanction', true);
    }

    public function appeal(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'appeal', true);
    }

    public function telemetry(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'telemetry');
    }

    public function funnel(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'funnel');
    }

    public function balance(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'balance', false, false);
    }

    public function economy(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'economy', false, false);
    }

    public function fraud(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'fraud', true);
    }

    public function health(Request $request): JsonResponse
    {
        return $this->recordTyped($request, 'health', false, false);
    }

    public function resolve(Request $request, ModerationAndAnalyticsRecord $moderationAndAnalytics): JsonResponse
    {
        $moderationAndAnalytics = $this->authorizedRecord($request, $moderationAndAnalytics);
        $v = $request->validate(['status' => ['nullable', 'in:resolved,dismissed,active,revoked']]);

        return response()->json(['data' => $this->resource(app(ModerationAndAnalyticsManager::class)->resolve($moderationAndAnalytics, $v['status'] ?? 'resolved'))]);
    }

    public function show(Request $request, ModerationAndAnalyticsRecord $moderationAndAnalytics): JsonResponse
    {
        $moderationAndAnalytics = $this->authorizedRecord($request, $moderationAndAnalytics);

        return response()->json(['data' => $this->resource($moderationAndAnalytics)]);
    }

    private function resource(ModerationAndAnalyticsRecord $moderationAndAnalytics): array
    {
        return ['id' => (string) $moderationAndAnalytics->getKey(), 'type' => 'browser-game-moderation-and-analytics', 'attributes' => ['name' => $moderationAndAnalytics->name, 'kind' => $moderationAndAnalytics->kind, 'status' => $moderationAndAnalytics->status, 'actor_id' => $moderationAndAnalytics->actor_id, 'target_id' => $moderationAndAnalytics->target_id, 'severity' => $moderationAndAnalytics->severity, 'value' => $moderationAndAnalytics->value, 'data' => $moderationAndAnalytics->data, 'tenant_id' => $moderationAndAnalytics->tenant_id, 'team_id' => $moderationAndAnalytics->team_id, 'resolved_at' => $moderationAndAnalytics->resolved_at?->toISOString(), 'created_at' => $moderationAndAnalytics->created_at?->toISOString(), 'updated_at' => $moderationAndAnalytics->updated_at?->toISOString()]];
    }

    private function authorizedRecord(Request $request, ModerationAndAnalyticsRecord $record): ModerationAndAnalyticsRecord
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        return app(ModerationAndAnalyticsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())->whereKey($record->getKey())->firstOrFail();
    }

    private function recordTyped(Request $request, string $kind, bool $targetRequired = false, bool $actorRequired = true): JsonResponse
    {
        $rules = ['name' => ['required', 'string', 'max:255'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']];
        $rules['target_id'] = [$targetRequired ? 'required' : 'nullable', 'string'];
        $v = $request->validate($rules);
        $actorId = $actorRequired ? (string) $request->user()->getAuthIdentifier() : null;
        $team = $request->user()?->currentTeam;
        $data = $v['data'] ?? [];
        if ($kind === 'appeal') {
            $data['reason'] = $data['reason'] ?? $v['name'];
        }
        $manager = app(ModerationAndAnalyticsManager::class);
        $tenantId = $team?->getAttribute('tenant_id');
        $teamId = $team?->getKey() === null ? null : (string) $team->getKey();
        $record = match ($kind) {
            'telemetry' => $manager->recordTelemetry((string) $actorId, $v['name'], $data, $tenantId, $teamId, $v['idempotency_key'] ?? null),
            'funnel' => $manager->recordFunnel((string) $actorId, $v['name'], $data, $tenantId, $teamId, $v['idempotency_key'] ?? null),
            'balance' => $manager->recordBalance($v['name'], $data, $tenantId, $teamId, $v['idempotency_key'] ?? null),
            'economy' => $manager->recordEconomy($v['name'], $data, $tenantId, $teamId, $v['idempotency_key'] ?? null),
            'fraud' => $manager->recordFraud((string) $actorId, $v['target_id'], $v['name'], $data, $tenantId, $teamId, $v['idempotency_key'] ?? null),
            default => $manager->record($kind, $v['name'], $actorId, $v['target_id'] ?? null, $data, $tenantId, $teamId, $v['idempotency_key'] ?? null),
        };

        return response()->json(['data' => $this->resource($record)], 201);
    }
}
