<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Collections\Models\CollectionsRecord;
use Liberu\BrowserGame\Collections\Queries\CollectionsQuery;
use Liberu\BrowserGame\Collections\Support\CollectionsManager;

final class CollectionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(CollectionsQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(Request $request, CollectionsRecord $collections): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $collections = app(CollectionsQuery::class)->visible(null, (string) $teamId)
            ->whereKey($collections->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($collections)]);
    }

    public function record(Request $request, CollectionsRecord $collections): JsonResponse
    {
        $validated = $request->validate(['entry_key' => ['required', 'string', 'max:128'], 'quantity' => ['nullable', 'integer', 'min:1']]);
        $progress = app(CollectionsManager::class)->record((string) $request->user()->getAuthIdentifier(), $collections, $validated['entry_key'], $validated['quantity'] ?? 1);

        return response()->json(['data' => $progress], 201);
    }

    private function resource(Model $collections): array
    {
        return ['id' => (string) $collections->getKey(), 'type' => 'browser-game-collections', 'attributes' => ['name' => $collections->getAttribute('name'), 'status' => $collections->getAttribute('status'), 'data' => $collections->getAttribute('data'), 'tenant_id' => $collections->getAttribute('tenant_id'), 'team_id' => $collections->getAttribute('team_id')]];
    }
}
