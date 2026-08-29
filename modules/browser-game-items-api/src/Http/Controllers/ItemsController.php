<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Items\Models\InventoryEntry;
use Liberu\BrowserGame\Items\Models\ItemsRecord;
use Liberu\BrowserGame\Items\Queries\ItemsQuery;
use Liberu\BrowserGame\Items\Support\ItemsManager;

final class ItemsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $items = app(ItemsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

        return response()->json($items->through(fn (Model $item): array => $this->resource($item)));
    }

    public function show(Request $request, ItemsRecord $items): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        $items = app(ItemsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())
            ->whereKey($items->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($items)]);
    }

    public function inventory(Request $request): JsonResponse
    {
        [$tenantId, $teamId] = $this->scope($request);

        return response()->json(['data' => app(ItemsManager::class)->inventory((string) $request->user()->getAuthIdentifier(), $tenantId, $teamId)->map(fn (InventoryEntry $entry): array => $this->inventoryResource($entry))->values()]);
    }

    public function addToInventory(Request $request, ItemsRecord $item): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100000']]);
        [$tenantId, $teamId] = $this->scope($request);
        $entry = app(ItemsManager::class)->addToInventory((string) $request->user()->getAuthIdentifier(), $item, (int) $data['quantity'], tenantId: $tenantId, teamId: $teamId);

        return response()->json(['data' => $this->inventoryResource($entry)], 201);
    }

    public function removeFromInventory(Request $request, ItemsRecord $item): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100000']]);
        [$tenantId, $teamId] = $this->scope($request);
        abort_unless(app(ItemsManager::class)->removeFromInventory((string) $request->user()->getAuthIdentifier(), $item, (int) $data['quantity'], $tenantId, $teamId), 422, 'Insufficient inventory.');

        return response()->json(['data' => ['removed' => true]]);
    }

    public function equip(Request $request, InventoryEntry $entry): JsonResponse
    {
        $data = $request->validate(['slot' => ['nullable', 'string', 'max:100']]);
        [$tenantId, $teamId] = $this->scope($request);
        $equipped = app(ItemsManager::class)->equip((string) $request->user()->getAuthIdentifier(), $entry, $data['slot'] ?? null, $tenantId, $teamId);

        return response()->json(['data' => $this->inventoryResource($equipped)]);
    }

    public function unequip(Request $request, InventoryEntry $entry): JsonResponse
    {
        [$tenantId, $teamId] = $this->scope($request);
        $equipped = app(ItemsManager::class)->unequip((string) $request->user()->getAuthIdentifier(), $entry, $tenantId, $teamId);

        return response()->json(['data' => $this->inventoryResource($equipped)]);
    }

    public function bind(Request $request, InventoryEntry $entry): JsonResponse
    {
        [$tenantId, $teamId] = $this->scope($request);
        $bound = app(ItemsManager::class)->bind((string) $request->user()->getAuthIdentifier(), $entry, $tenantId, $teamId);

        return response()->json(['data' => $this->inventoryResource($bound)]);
    }

    public function durability(Request $request, InventoryEntry $entry): JsonResponse
    {
        $data = $request->validate(['delta' => ['required', 'integer', 'between:-100000,100000']]);
        [$tenantId, $teamId] = $this->scope($request);
        $updated = app(ItemsManager::class)->adjustDurability((string) $request->user()->getAuthIdentifier(), $entry, (int) $data['delta'], $tenantId, $teamId);

        return response()->json(['data' => $this->inventoryResource($updated)]);
    }

    public function container(Request $request, InventoryEntry $entry): JsonResponse
    {
        $data = $request->validate(['container_id' => ['required', 'integer', 'min:1']]);
        [$tenantId, $teamId] = $this->scope($request);
        $updated = app(ItemsManager::class)->putInContainer((string) $request->user()->getAuthIdentifier(), $entry, (int) $data['container_id'], $tenantId, $teamId);

        return response()->json(['data' => $this->inventoryResource($updated)]);
    }

    public function provenance(Request $request, InventoryEntry $entry): JsonResponse
    {
        $data = $request->validate(['provenance' => ['required', 'array']]);
        [$tenantId, $teamId] = $this->scope($request);
        $updated = app(ItemsManager::class)->setProvenance((string) $request->user()->getAuthIdentifier(), $entry, $data['provenance'], $tenantId, $teamId);

        return response()->json(['data' => $this->inventoryResource($updated)]);
    }

    private function resource(Model $items): array
    {
        return ['id' => (string) $items->getKey(), 'type' => 'browser-game-items', 'attributes' => [
            'name' => $items->getAttribute('name'),
            'description' => $items->getAttribute('description'),
            'type' => $items->getAttribute('type'),
            'rarity' => $items->getAttribute('rarity'),
            'slot' => $items->getAttribute('slot'),
            'stats' => collect(['strength_bonus', 'defense_bonus', 'agility_bonus', 'intelligence_bonus', 'health_bonus', 'mana_bonus'])->mapWithKeys(fn (string $key): array => [$key => $items->getAttribute($key)])->all(),
            'min_level' => $items->getAttribute('min_level'),
            'sell_price' => $items->getAttribute('sell_price'),
            'buy_price' => $items->getAttribute('buy_price'),
            'max_durability' => $items->getAttribute('max_durability'),
            'status' => $items->getAttribute('status'),
            'data' => $items->getAttribute('data'),
            'tenant_id' => $items->getAttribute('tenant_id'),
            'team_id' => $items->getAttribute('team_id'),
        ]];
    }

    private function inventoryResource(InventoryEntry $entry): array
    {
        return ['id' => (string) $entry->getKey(), 'type' => 'browser-game-inventory-entry', 'attributes' => [
            'player_id' => $entry->player_id,
            'item_id' => (string) $entry->item_id,
            'quantity' => $entry->quantity,
            'equipment_slot' => $entry->equipment_slot,
            'durability' => $entry->durability,
            'max_durability' => $entry->max_durability,
            'is_bound' => $entry->is_bound,
            'equipped_at' => $entry->equipped_at,
            'container_id' => $entry->container_id,
            'provenance' => $entry->provenance,
            'item' => $entry->item?->only(['id', 'name', 'type', 'rarity']),
        ]];
    }

    /** @return array{0: ?string, 1: ?string} */
    private function scope(Request $request): array
    {
        $team = $request->user()?->currentTeam;

        return [$team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey()];
    }
}
