<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Items\Events\ItemsDefined;
use Liberu\BrowserGame\Items\Models\InventoryEntry;
use Liberu\BrowserGame\Items\Models\ItemsRecord;

final class ItemsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): ItemsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): ItemsRecord => ItemsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
            ...array_intersect_key($data, array_flip([
                'description', 'type', 'rarity', 'slot', 'strength_bonus', 'defense_bonus',
                'agility_bonus', 'intelligence_bonus', 'health_bonus', 'mana_bonus',
                'max_durability', 'max_stack', 'min_level', 'sell_price', 'buy_price',
            ])),
        ]));
        ItemsDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function addToInventory(string|int $playerId, ItemsRecord|string $item, int $quantity = 1, array $provenance = []): InventoryEntry
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least one.']);
        }

        $itemId = $item instanceof ItemsRecord ? (string) $item->getKey() : $item;
        $record = ItemsRecord::query()->findOrFail($itemId);
        if ($record->status !== 'active') {
            throw ValidationException::withMessages(['item' => 'The item is not available.']);
        }

        return DB::transaction(function () use ($playerId, $record, $quantity, $provenance): InventoryEntry {
            $entry = InventoryEntry::query()->lockForUpdate()->firstOrCreate(
                ['player_id' => (string) $playerId, 'item_id' => $record->getKey()],
                [
                    'quantity' => 0,
                    'max_durability' => $record->getAttribute('max_durability'),
                    'durability' => $record->getAttribute('max_durability'),
                    'provenance' => $provenance,
                ],
            );
            $maxStack = $record->getAttribute('max_stack');
            if ($maxStack !== null && ((int) $entry->quantity + $quantity) > (int) $maxStack) {
                throw ValidationException::withMessages(['quantity' => 'The item stack limit would be exceeded.']);
            }
            $entry->increment('quantity', $quantity);

            return $entry->fresh('item');
        });
    }

    public function removeFromInventory(string|int $playerId, ItemsRecord|string $item, int $quantity = 1): bool
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least one.']);
        }

        $itemId = $item instanceof ItemsRecord ? (string) $item->getKey() : $item;

        return DB::transaction(function () use ($playerId, $itemId, $quantity): bool {
            $entry = InventoryEntry::query()->lockForUpdate()
                ->where('player_id', (string) $playerId)
                ->where('item_id', $itemId)
                ->first();

            if ($entry === null || $entry->quantity < $quantity) {
                return false;
            }

            $entry->quantity === $quantity ? $entry->delete() : $entry->decrement('quantity', $quantity);

            return true;
        });
    }

    public function inventory(string|int $playerId): Collection
    {
        return InventoryEntry::query()->with('item')->where('player_id', (string) $playerId)->get();
    }

    public function equip(string|int $playerId, InventoryEntry|int $entry, ?string $slot = null): InventoryEntry
    {
        return DB::transaction(function () use ($playerId, $entry, $slot): InventoryEntry {
            $inventoryEntry = $this->lockedOwnedEntry($playerId, $entry);
            $equipmentSlot = $slot ?? $inventoryEntry->item?->getAttribute('slot');
            if ($equipmentSlot !== null) {
                InventoryEntry::query()->where('player_id', (string) $playerId)
                    ->where('equipment_slot', $equipmentSlot)
                    ->whereKeyNot($inventoryEntry->getKey())
                    ->lockForUpdate()->get()->each(fn (InventoryEntry $equipped): bool => (bool) $equipped->update(['equipment_slot' => null, 'equipped_at' => null]));
            }
            $inventoryEntry->update([
                'is_bound' => true,
                'bound_at' => $inventoryEntry->bound_at ?? now(),
                'equipment_slot' => $equipmentSlot,
                'equipped_at' => now(),
            ]);

            return $inventoryEntry->fresh('item');
        });
    }

    public function unequip(string|int $playerId, InventoryEntry|int $entry): InventoryEntry
    {
        return DB::transaction(function () use ($playerId, $entry): InventoryEntry {
            $inventoryEntry = $this->lockedOwnedEntry($playerId, $entry);
            $inventoryEntry->update(['equipment_slot' => null, 'equipped_at' => null]);

            return $inventoryEntry->fresh('item');
        });
    }

    public function bind(string|int $playerId, InventoryEntry|int $entry): InventoryEntry
    {
        return DB::transaction(function () use ($playerId, $entry): InventoryEntry {
            $inventoryEntry = $this->lockedOwnedEntry($playerId, $entry);
            $inventoryEntry->update(['is_bound' => true, 'bound_at' => $inventoryEntry->bound_at ?? now()]);

            return $inventoryEntry->fresh('item');
        });
    }

    public function setProvenance(string|int $playerId, InventoryEntry|int $entry, array $provenance): InventoryEntry
    {
        return DB::transaction(function () use ($playerId, $entry, $provenance): InventoryEntry {
            $inventoryEntry = $this->lockedOwnedEntry($playerId, $entry);
            $inventoryEntry->update(['provenance' => $provenance]);

            return $inventoryEntry->fresh('item');
        });
    }

    public function adjustDurability(string|int $playerId, InventoryEntry|int $entry, int $delta): InventoryEntry
    {
        return DB::transaction(function () use ($playerId, $entry, $delta): InventoryEntry {
            $inventoryEntry = $this->lockedOwnedEntry($playerId, $entry);
            $max = (int) ($inventoryEntry->max_durability ?? 0);
            if ($max < 1) {
                throw ValidationException::withMessages(['durability' => 'This item does not use durability.']);
            }

            $current = max(0, min($max, (int) ($inventoryEntry->durability ?? $max) + $delta));
            $inventoryEntry->update(['durability' => $current]);

            return $inventoryEntry->fresh('item');
        });
    }

    public function putInContainer(string|int $playerId, InventoryEntry|int $entry, InventoryEntry|int $container): InventoryEntry
    {
        return DB::transaction(function () use ($playerId, $entry, $container): InventoryEntry {
            $inventoryEntry = $this->lockedOwnedEntry($playerId, $entry);
            $containerEntry = $this->lockedOwnedEntry($playerId, $container);
            if ($inventoryEntry->is($containerEntry)) {
                throw ValidationException::withMessages(['container' => 'An item cannot contain itself.']);
            }
            if ($containerEntry->item?->getAttribute('type') !== 'container') {
                throw ValidationException::withMessages(['container' => 'The selected item is not a container.']);
            }
            $ancestorId = $containerEntry->container_id;
            $visited = [];
            while ($ancestorId !== null) {
                if ((int) $ancestorId === (int) $inventoryEntry->getKey() || isset($visited[(int) $ancestorId])) {
                    throw ValidationException::withMessages(['container' => 'Container nesting would create a cycle.']);
                }
                $visited[(int) $ancestorId] = true;
                $ancestorId = InventoryEntry::query()->whereKey($ancestorId)->lockForUpdate()->value('container_id');
            }

            $inventoryEntry->update(['container_id' => $containerEntry->getKey()]);

            return $inventoryEntry->fresh(['item', 'container']);
        });
    }

    private function ownedEntry(string|int $playerId, InventoryEntry|int $entry): InventoryEntry
    {
        return InventoryEntry::query()->with('item')
            ->where('player_id', (string) $playerId)
            ->whereKey($entry instanceof InventoryEntry ? $entry->getKey() : $entry)
            ->firstOrFail();
    }

    private function lockedOwnedEntry(string|int $playerId, InventoryEntry|int $entry): InventoryEntry
    {
        return InventoryEntry::query()->with('item')->where('player_id', (string) $playerId)
            ->whereKey($entry instanceof InventoryEntry ? $entry->getKey() : $entry)
            ->lockForUpdate()->firstOrFail();
    }
}
