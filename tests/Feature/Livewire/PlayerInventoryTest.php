<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PlayerInventory;
use App\Models\Item;
use App\Models\Player;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayerInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_successfully()
    {
        Player::factory()->create();

        Livewire::test(PlayerInventory::class)
            ->assertStatus(200);
    }

    public function test_displays_player_items()
    {
        $player = Player::factory()->create();
        $item = Item::factory()->create(['name' => 'Health Potion']);

        $player->items()->attach($item->id, ['quantity' => 5]);

        Livewire::test(PlayerInventory::class)
            ->assertSee('Health Potion')
            ->assertSee('x5');
    }

    public function test_displays_player_resources()
    {
        $player = Player::factory()->create();
        Resource::factory()->create([
            'player_id' => $player->id,
            'resource_type' => 'gold',
            'quantity' => 1000,
        ]);

        Livewire::test(PlayerInventory::class)
            ->assertSee('gold')
            ->assertSee('1000');
    }

    public function test_player_can_use_item()
    {
        $player = Player::factory()->create();
        $item = Item::factory()->create(['name' => 'Mana Potion']);

        $player->items()->attach($item->id, ['quantity' => 3]);

        Livewire::test(PlayerInventory::class)
            ->call('useItem', $item->id)
            ->assertDispatched('item-used')
            ->assertSee('Used');

        // Verify quantity decreased
        $this->assertEquals(2, $player->fresh()->items()->where('item_id', $item->id)->first()->pivot->quantity);
    }

    public function test_player_can_drop_item()
    {
        $player = Player::factory()->create();
        $item = Item::factory()->create(['name' => 'Old Sword']);

        $player->items()->attach($item->id, ['quantity' => 1]);

        Livewire::test(PlayerInventory::class)
            ->call('dropItem', $item->id)
            ->assertDispatched('item-dropped')
            ->assertSee('Dropped');

        // Verify item removed
        $this->assertFalse($player->fresh()->items->contains($item));
    }

    public function test_using_last_item_removes_it_from_inventory()
    {
        $player = Player::factory()->create();
        $item = Item::factory()->create(['name' => 'Last Potion']);

        $player->items()->attach($item->id, ['quantity' => 1]);

        Livewire::test(PlayerInventory::class)
            ->call('useItem', $item->id);

        // Verify item removed when quantity reaches 0
        $this->assertFalse($player->fresh()->items->contains($item));
    }

    public function test_inventory_refreshes_on_player_update()
    {
        $player = Player::factory()->create();

        Livewire::test(PlayerInventory::class)
            ->call('refreshInventory')
            ->assertStatus(200);
    }

    public function test_displays_item_rarity()
    {
        $player = Player::factory()->create();
        $item = Item::factory()->create([
            'name' => 'Legendary Sword',
            'rarity' => 'legendary',
        ]);

        $player->items()->attach($item->id, ['quantity' => 1]);

        Livewire::test(PlayerInventory::class)
            ->assertSee('Legendary Sword')
            ->assertSee('Legendary');
    }
}
