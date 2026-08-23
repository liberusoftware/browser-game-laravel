<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Item;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    private Player $player;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = new InventoryService();

        // Create test data
        $this->player = Player::factory()->create();
        $this->item = Item::factory()->create([
            'name' => 'Test Sword',
            'type' => 'weapon',
            'rarity' => 'common',
        ]);
    }

    public function test_can_add_item_to_inventory()
    {
        $playerItem = $this->inventoryService->addItem($this->player->id, $this->item->id, 5);

        $this->assertInstanceOf(Player_Item::class, $playerItem);
        $this->assertEquals(5, $playerItem->quantity);
        $this->assertEquals($this->player->id, $playerItem->player_id);
        $this->assertEquals($this->item->id, $playerItem->item_id);
    }

    public function test_adding_existing_item_increases_quantity()
    {
        // Add item first time
        $this->inventoryService->addItem($this->player->id, $this->item->id, 3);

        // Add same item again
        $playerItem = $this->inventoryService->addItem($this->player->id, $this->item->id, 2);

        $this->assertEquals(5, $playerItem->quantity);

        // Verify only one record exists
        $count = Player_Item::where('player_id', $this->player->id)
            ->where('item_id', $this->item->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_can_remove_item_from_inventory()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 10,
        ]);

        $result = $this->inventoryService->removeItem($this->player->id, $this->item->id, 3);

        $this->assertTrue($result);

        $playerItem = Player_Item::where('player_id', $this->player->id)
            ->where('item_id', $this->item->id)
            ->first();
        $this->assertEquals(7, $playerItem->quantity);
    }

    public function test_removing_all_quantity_deletes_record()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        $result = $this->inventoryService->removeItem($this->player->id, $this->item->id, 5);

        $this->assertTrue($result);

        $exists = Player_Item::where('player_id', $this->player->id)
            ->where('item_id', $this->item->id)
            ->exists();
        $this->assertFalse($exists);
    }

    public function test_cannot_remove_more_than_available()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 3,
        ]);

        $result = $this->inventoryService->removeItem($this->player->id, $this->item->id, 5);

        $this->assertFalse($result);

        // Quantity should remain unchanged
        $playerItem = Player_Item::where('player_id', $this->player->id)
            ->where('item_id', $this->item->id)
            ->first();
        $this->assertEquals(3, $playerItem->quantity);
    }

    public function test_can_get_player_inventory()
    {
        $item1 = Item::factory()->create(['name' => 'Sword']);
        $item2 = Item::factory()->create(['name' => 'Shield']);

        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $item1->id,
            'quantity' => 5,
        ]);

        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $item2->id,
            'quantity' => 3,
        ]);

        $inventory = $this->inventoryService->getPlayerInventory($this->player->id);

        $this->assertCount(2, $inventory);
        $this->assertTrue($inventory->contains('item_id', $item1->id));
        $this->assertTrue($inventory->contains('item_id', $item2->id));
    }

    public function test_inventory_is_cached()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        // First call - should cache
        $inventory1 = $this->inventoryService->getPlayerInventory($this->player->id);

        // Manually add item to database (bypassing service)
        $newItem = Item::factory()->create();
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $newItem->id,
            'quantity' => 1,
        ]);

        // Second call - should return cached result (not include new item)
        $inventory2 = $this->inventoryService->getPlayerInventory($this->player->id);

        $this->assertCount(1, $inventory2); // Still 1 item from cache

        // Clear cache and verify we get updated data
        Cache::forget("player.{$this->player->id}.inventory");
        $inventory3 = $this->inventoryService->getPlayerInventory($this->player->id);

        $this->assertCount(2, $inventory3); // Now 2 items
    }

    public function test_cache_is_invalidated_on_add_item()
    {
        // Prime the cache
        $this->inventoryService->getPlayerInventory($this->player->id);

        // Add item through service (should invalidate cache)
        $this->inventoryService->addItem($this->player->id, $this->item->id, 1);

        // Getting inventory again should reflect the new item
        $inventory = $this->inventoryService->getPlayerInventory($this->player->id);

        $this->assertCount(1, $inventory);
        $this->assertEquals($this->item->id, $inventory->first()->item_id);
    }

    public function test_cache_is_invalidated_on_remove_item()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        // Prime the cache
        $this->inventoryService->getPlayerInventory($this->player->id);

        // Remove item through service (should invalidate cache)
        $this->inventoryService->removeItem($this->player->id, $this->item->id, 5);

        // Getting inventory again should reflect the removal
        $inventory = $this->inventoryService->getPlayerInventory($this->player->id);

        $this->assertCount(0, $inventory);
    }

    public function test_can_get_specific_player_item()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 7,
        ]);

        $playerItem = $this->inventoryService->getPlayerItem($this->player->id, $this->item->id);

        $this->assertNotNull($playerItem);
        $this->assertEquals(7, $playerItem->quantity);
        $this->assertNotNull($playerItem->item);
        $this->assertEquals($this->item->name, $playerItem->item->name);
    }

    public function test_can_update_item_quantity()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        $updated = $this->inventoryService->updateItemQuantity($this->player->id, $this->item->id, 10);

        $this->assertNotNull($updated);
        $this->assertEquals(10, $updated->quantity);
    }

    public function test_updating_quantity_to_zero_deletes_item()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        $result = $this->inventoryService->updateItemQuantity($this->player->id, $this->item->id, 0);

        $this->assertNull($result);

        $exists = Player_Item::where('player_id', $this->player->id)
            ->where('item_id', $this->item->id)
            ->exists();
        $this->assertFalse($exists);
    }

    public function test_can_get_inventory_stats()
    {
        $weapon = Item::factory()->create(['type' => 'weapon']);
        $armor = Item::factory()->create(['type' => 'armor']);

        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $weapon->id,
            'quantity' => 5,
        ]);

        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $armor->id,
            'quantity' => 3,
        ]);

        $stats = $this->inventoryService->getInventoryStats($this->player->id);

        $this->assertEquals(8, $stats['total_items']); // 5 + 3
        $this->assertEquals(2, $stats['unique_items']);
        $this->assertArrayHasKey('weapon', $stats['items_by_type']);
        $this->assertArrayHasKey('armor', $stats['items_by_type']);
    }

    public function test_inventory_stats_are_cached()
    {
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        // First call - caches stats
        $stats1 = $this->inventoryService->getInventoryStats($this->player->id);

        // Add more items directly to database
        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => Item::factory()->create()->id,
            'quantity' => 10,
        ]);

        // Second call - should return cached stats
        $stats2 = $this->inventoryService->getInventoryStats($this->player->id);

        $this->assertEquals(5, $stats2['total_items']); // Still showing cached value
    }
}
