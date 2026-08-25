<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Collections\Models\CollectionProgress;
use Liberu\BrowserGame\Collections\Models\CollectionsRecord;
use Liberu\BrowserGame\Collections\Queries\CollectionsQuery;
use Liberu\BrowserGame\Collections\Support\CollectionsManager;

final class CollectionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $items = app(CollectionsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate($pageSize);

        return response()->json(['data' => $items->through(fn (CollectionsRecord $item): array => $this->resource($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['nullable', 'in:achievement,title,reputation,pet,mount,housing,cosmetic'],
            'repeatable' => ['nullable', 'boolean'],
            'data' => ['array'],
        ]);
        $team = $request->user()?->currentTeam;
        $collection = app(CollectionsManager::class)->defineCollection($data['name'], $data['kind'] ?? 'achievement', $data['data'] ?? [], (bool) ($data['repeatable'] ?? false), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->resource($collection)], 201);
    }

    public function show(Request $request, CollectionsRecord $collections): JsonResponse
    {
        $collections = $this->authorizedCollection($request, $collections);

        return response()->json(['data' => $this->resource($collections)]);
    }

    public function progress(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $visibleCollections = app(CollectionsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey());
        $items = CollectionProgress::query()
            ->where('actor_id', (string) $request->user()->getAuthIdentifier())
            ->whereIn('collection_id', $visibleCollections->select('id'))
            ->latest()
            ->paginate($pageSize);

        return response()->json(['data' => $items->through(fn (CollectionProgress $progress): array => $this->progressResource($progress))]);
    }

    public function record(Request $request, CollectionsRecord $collections): JsonResponse
    {
        $collections = $this->authorizedCollection($request, $collections);
        $validated = $request->validate(['entry_key' => ['required', 'string', 'max:128'], 'quantity' => ['nullable', 'integer', 'min:1'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $team = $request->user()?->currentTeam;
        $progress = app(CollectionsManager::class)->record((string) $request->user()->getAuthIdentifier(), $collections, $validated['entry_key'], $validated['quantity'] ?? 1, $validated['idempotency_key'] ?? $request->header('Idempotency-Key'), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->progressResource($progress)], 201);
    }

    private function resource(CollectionsRecord $collections): array
    {
        return ['id' => (string) $collections->getKey(), 'type' => 'browser-game-collections', 'attributes' => ['name' => $collections->name, 'kind' => $collections->kind, 'status' => $collections->status, 'repeatable' => $collections->repeatable, 'data' => $collections->data, 'tenant_id' => $collections->tenant_id, 'team_id' => $collections->team_id, 'created_at' => $collections->created_at?->toISOString(), 'updated_at' => $collections->updated_at?->toISOString()]];
    }

    private function progressResource(CollectionProgress $progress): array
    {
        return ['id' => (string) $progress->getKey(), 'type' => 'browser-game-collection-progress', 'attributes' => ['collection_id' => (string) $progress->collection_id, 'entry_key' => $progress->entry_key, 'quantity' => $progress->quantity, 'completion_count' => $progress->completion_count, 'completed_at' => $progress->completed_at?->toISOString(), 'reward_claimed_at' => $progress->reward_claimed_at?->toISOString(), 'data' => $progress->data]];
    }

    private function authorizedCollection(Request $request, CollectionsRecord $collection): CollectionsRecord
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        return app(CollectionsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())->whereKey($collection->getKey())->firstOrFail();
    }
}
