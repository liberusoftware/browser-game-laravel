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
        $team = $request->user()?->currentTeam;
        $items = app(CommerceQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

        return response()->json($items->through(fn (CommerceRecord $item): array => $this->resource($item)));
    }

    public function products(Request $request): JsonResponse
    {
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $team = $request->user()?->currentTeam;

        $products = CommerceProduct::query()
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()))
            ->latest()
            ->paginate($pageSize);

        return response()->json($products->through(fn (CommerceProduct $product): array => $this->productResource($product)));
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate(['lines' => ['required', 'array', 'min:1'], 'lines.*.product_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);
        $order = app(CommerceManager::class)->checkout((string) $request->user()->getAuthIdentifier(), $validated['lines'], $validated['idempotency_key'] ?? null, $team->getAttribute('tenant_id'), (string) $team->getKey());

        return response()->json(['data' => $this->orderResource($order)], 201);
    }

    public function complete(Request $request, CommerceOrder $order): JsonResponse
    {
        abort_unless((string) $order->actor_id === (string) $request->user()->getAuthIdentifier(), 404);
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null && $this->inScope($order, $team), 404);

        return response()->json(['data' => $this->orderResource(app(CommerceManager::class)->complete($order, (string) $request->user()->getAuthIdentifier(), $team->getAttribute('tenant_id'), (string) $team->getKey()))]);
    }

    public function refund(Request $request, CommerceOrder $order): JsonResponse
    {
        abort_unless((string) $order->actor_id === (string) $request->user()->getAuthIdentifier(), 404);
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null && $this->inScope($order, $team), 404);

        return response()->json(['data' => $this->orderResource(app(CommerceManager::class)->refund($order, (string) $request->user()->getAuthIdentifier(), $team->getAttribute('tenant_id'), (string) $team->getKey()))]);
    }

    public function show(Request $request, CommerceRecord $commerce): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        $commerce = app(CommerceQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())
            ->whereKey($commerce->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($commerce)]);
    }

    private function resource(CommerceRecord $commerce): array
    {
        return ['id' => (string) $commerce->getKey(), 'type' => 'browser-game-commerce', 'attributes' => ['name' => $commerce->name, 'status' => $commerce->status, 'data' => $commerce->data, 'tenant_id' => $commerce->tenant_id, 'team_id' => $commerce->team_id, 'created_at' => $commerce->created_at?->toISOString(), 'updated_at' => $commerce->updated_at?->toISOString()]];
    }

    private function productResource(CommerceProduct $product): array
    {
        return ['id' => (string) $product->getKey(), 'type' => 'browser-game-commerce-product', 'attributes' => ['sku' => $product->sku, 'name' => $product->name, 'status' => $product->status, 'currency_code' => $product->currency_code, 'price' => $product->price, 'stock' => $product->stock, 'max_per_actor' => $product->max_per_actor, 'delivery' => $product->delivery, 'data' => $product->data, 'tenant_id' => $product->tenant_id, 'team_id' => $product->team_id]];
    }

    private function orderResource(CommerceOrder $order): array
    {
        return ['id' => (string) $order->getKey(), 'type' => 'browser-game-commerce-order', 'attributes' => ['actor_id' => (string) $order->actor_id, 'tenant_id' => $order->tenant_id, 'team_id' => $order->team_id, 'currency_code' => $order->currency_code, 'subtotal' => $order->subtotal, 'total' => $order->total, 'status' => $order->status, 'completed_at' => $order->completed_at?->toISOString(), 'lines' => $order->relationLoaded('lines') ? $order->lines->map(fn ($line): array => ['product_id' => (string) $line->product_id, 'quantity' => $line->quantity, 'unit_price' => $line->unit_price, 'line_total' => $line->line_total, 'delivery' => $line->delivery])->values() : [], 'entitlements' => $order->relationLoaded('entitlements') ? $order->entitlements->map(fn ($entitlement): array => ['id' => (string) $entitlement->getKey(), 'product_id' => (string) $entitlement->product_id, 'delivery_key' => $entitlement->delivery_key, 'quantity' => $entitlement->quantity, 'status' => $entitlement->status, 'data' => $entitlement->data])->values() : []]];
    }

    private function inScope(CommerceOrder $order, $team): bool
    {
        return ($order->tenant_id === null || (string) $order->tenant_id === (string) $team->getAttribute('tenant_id'))
            && ($order->team_id === null || (string) $order->team_id === (string) $team->getKey());
    }
}
