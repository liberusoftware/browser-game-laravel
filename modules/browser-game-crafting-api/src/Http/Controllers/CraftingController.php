<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Crafting\Models\CraftingProfession;
use Liberu\BrowserGame\Crafting\Models\CraftingQueue;
use Liberu\BrowserGame\Crafting\Models\CraftingRecord;
use Liberu\BrowserGame\Crafting\Models\CraftingResource;
use Liberu\BrowserGame\Crafting\Queries\CraftingQuery;
use Liberu\BrowserGame\Crafting\Support\CraftingManager;

final class CraftingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(CraftingQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (CraftingRecord $item): array => $this->resource($item))]);
    }

    public function show(Request $request, CraftingRecord $crafting): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $crafting = app(CraftingQuery::class)->visible(null, (string) $teamId)
            ->whereKey($crafting->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($crafting)]);
    }

    public function queue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipe_id' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'quality' => ['nullable', 'integer', 'min:0', 'max:100'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
        ]);
        $recipe = CraftingRecord::query()->whereKey($data['recipe_id'])->where('status', 'active')->firstOrFail();
        $queue = app(CraftingManager::class)->queueCraft((string) $request->user()->getAuthIdentifier(), $recipe, (int) $data['quantity'], (int) ($data['quality'] ?? 100), $this->operationKey($request, $data['idempotency_key'] ?? null));

        return response()->json(['data' => $this->queueResource($queue)], 202);
    }

    public function queues(Request $request): JsonResponse
    {
        $queues = CraftingQueue::query()->with('recipe')->where('actor_id', (string) $request->user()->getAuthIdentifier())->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $queues->through(fn (CraftingQueue $queue): array => $this->queueResource($queue))]);
    }

    public function complete(Request $request, CraftingQueue $queue): JsonResponse
    {
        $this->assertOwner($request, $queue);
        $updated = app(CraftingManager::class)->complete($queue);

        return response()->json(['data' => $this->queueResource($updated)]);
    }

    public function cancel(Request $request, CraftingQueue $queue): JsonResponse
    {
        $this->assertOwner($request, $queue);
        $updated = app(CraftingManager::class)->cancel($queue);

        return response()->json(['data' => $this->queueResource($updated)]);
    }

    public function salvage(Request $request, CraftingQueue $queue): JsonResponse
    {
        $this->assertOwner($request, $queue);
        $updated = app(CraftingManager::class)->salvage($queue);

        return response()->json(['data' => $this->queueResource($updated)]);
    }

    public function discover(Request $request, CraftingRecord $crafting): JsonResponse
    {
        $discovery = app(CraftingManager::class)->discover((string) $request->user()->getAuthIdentifier(), $crafting);

        return response()->json(['data' => ['id' => (string) $discovery->getKey(), 'type' => 'browser-game-crafting-discovery', 'attributes' => ['recipe_id' => $discovery->recipe_id, 'discovered_at' => $discovery->discovered_at]]], 201);
    }

    public function professions(Request $request): JsonResponse
    {
        return response()->json(['data' => CraftingProfession::query()->where('actor_id', (string) $request->user()->getAuthIdentifier())->get()->map(fn (CraftingProfession $profession): array => ['id' => (string) $profession->getKey(), 'type' => 'browser-game-crafting-profession', 'attributes' => $profession->only(['profession', 'level', 'experience'])])->values()]);
    }

    public function resources(Request $request): JsonResponse
    {
        $resources = CraftingResource::query()->where('actor_id', (string) $request->user()->getAuthIdentifier())->orderBy('resource_key')->get();

        return response()->json(['data' => $resources->map(fn (CraftingResource $resource): array => [
            'id' => (string) $resource->getKey(),
            'type' => 'browser-game-crafting-resource',
            'attributes' => ['resource_key' => $resource->resource_key, 'quantity' => $resource->quantity],
        ])->values()]);
    }

    private function resource(CraftingRecord $crafting): array
    {
        return ['id' => (string) $crafting->getKey(), 'type' => 'browser-game-crafting-recipe', 'attributes' => [
            'name' => $crafting->name, 'slug' => $crafting->slug, 'description' => $crafting->description,
            'profession' => $crafting->profession, 'min_level' => $crafting->min_level,
            'success_rate' => $crafting->success_rate, 'crafting_time_seconds' => $crafting->crafting_time_seconds,
            'materials' => $crafting->materials, 'outputs' => $crafting->outputs, 'salvage' => $crafting->salvage,
            'discovery_requirements' => $crafting->discovery_requirements, 'status' => $crafting->status,
        ]];
    }

    private function queueResource(CraftingQueue $queue): array
    {
        return ['id' => (string) $queue->getKey(), 'type' => 'browser-game-crafting-queue', 'attributes' => [
            'actor_id' => $queue->actor_id, 'recipe_id' => $queue->recipe_id, 'quantity' => $queue->quantity,
            'status' => $queue->status, 'quality' => $queue->quality, 'started_at' => $queue->started_at,
            'completes_at' => $queue->completes_at, 'completed_at' => $queue->completed_at,
            'failure_reason' => $queue->failure_reason, 'outputs' => $queue->outputs,
        ]];
    }

    private function assertOwner(Request $request, CraftingQueue $queue): void
    {
        abort_unless($queue->actor_id === (string) $request->user()->getAuthIdentifier(), 404);
    }

    private function operationKey(Request $request, ?string $bodyKey = null): ?string
    {
        $key = $request->header('Idempotency-Key') ?: $bodyKey;

        return is_string($key) && trim($key) !== '' ? substr(trim($key), 0, 191) : null;
    }
}
