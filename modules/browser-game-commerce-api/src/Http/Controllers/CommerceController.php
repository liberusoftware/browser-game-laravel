<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Commerce\Models\CommerceOrder;
use Liberu\BrowserGame\Commerce\Models\CommerceProduct;
use Liberu\BrowserGame\Commerce\Models\CommerceRecord;
use Liberu\BrowserGame\Commerce\Queries\CommerceQuery;
use Liberu\BrowserGame\Commerce\Support\CommerceManager;

final class CommerceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(CommerceQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (CommerceRecord $item): array => $this->resource($item))]);
    }

    public function products(Request $request): JsonResponse
    {
        return response()->json(['data' => CommerceProduct::query()->where('status', 'active')->latest()->paginate(min($request->integer('page_size', 25), 100))]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate(['lines' => ['required', 'array', 'min:1'], 'lines.*.product_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $order = app(CommerceManager::class)->checkout((string) $request->user()->getAuthIdentifier(), $validated['lines'], $validated['idempotency_key'] ?? null);

        return response()->json(['data' => $order], 201);
    }

    public function complete(Request $request, CommerceOrder $order): JsonResponse
    {
        abort_unless((string) $order->actor_id === (string) $request->user()->getAuthIdentifier(), 404);

        return response()->json(['data' => app(CommerceManager::class)->complete($order)]);
    }

    public function refund(Request $request, CommerceOrder $order): JsonResponse
    {
        abort_unless((string) $order->actor_id === (string) $request->user()->getAuthIdentifier(), 404);

        return response()->json(['data' => app(CommerceManager::class)->refund($order)]);
    }

    public function show(Request $request, CommerceRecord $commerce): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $commerce = app(CommerceQuery::class)->visible(null, (string) $teamId)
            ->whereKey($commerce->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($commerce)]);
    }

    private function resource(Model $commerce): array
    {
        return ['id' => (string) $commerce->getKey(), 'type' => 'browser-game-commerce', 'attributes' => ['name' => $commerce->getAttribute('name'), 'status' => $commerce->getAttribute('status'), 'data' => $commerce->getAttribute('data'), 'tenant_id' => $commerce->getAttribute('tenant_id'), 'team_id' => $commerce->getAttribute('team_id')]];
    }
}
