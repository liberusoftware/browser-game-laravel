<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\MarketplaceListing;
use App\Models\Player;
use App\Models\Player_Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Player $seller;

    protected Player $buyer;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = Player::factory()->create();
        $this->buyer = Player::factory()->create();
        Sanctum::actingAs($this->seller);
        $this->item = Item::factory()->create(['name' => 'Magic Staff']);

        // Give seller some items
        Player_Item::create([
            'player_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 10,
        ]);

        // Give buyer gold
        $this->buyer->resources()->create([
            'resource_type' => 'gold',
            'quantity' => 10000,
        ]);
    }

    #[Test]
    public function can_get_marketplace_listings(): void
    {
        MarketplaceListing::create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'price_per_unit' => 100,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/marketplace');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    #[Test]
    public function seller_can_create_a_listing(): void
    {
        $response = $this->postJson('/api/marketplace', [
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'price_per_unit' => 100,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Listing created successfully',
            ]);

        $this->assertDatabaseHas('marketplace_listings', [
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'status' => 'active',
        ]);

        // Verify items were deducted from seller
        $this->assertDatabaseHas('player__items', [
            'player_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function seller_cannot_list_more_than_they_own(): void
    {
        $response = $this->postJson('/api/marketplace', [
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
            'price_per_unit' => 100,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have enough of this item to list.',
            ]);
    }

    #[Test]
    public function buyer_can_purchase_a_listing(): void
    {
        Sanctum::actingAs($this->buyer);
        $listing = MarketplaceListing::create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'price_per_unit' => 100,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/marketplace/{$listing->id}/purchase", [
            'buyer_id' => $this->buyer->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purchase successful!',
            ]);

        $this->assertDatabaseHas('marketplace_listings', [
            'id' => $listing->id,
            'status' => 'sold',
        ]);

        // Buyer should now have the item
        $this->assertDatabaseHas('player__items', [
            'player_id' => $this->buyer->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function buyer_cannot_purchase_own_listing(): void
    {
        $listing = MarketplaceListing::create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'price_per_unit' => 100,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/marketplace/{$listing->id}/purchase", [
            'buyer_id' => $this->seller->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You cannot buy your own listing.',
            ]);
    }

    #[Test]
    public function seller_can_cancel_a_listing(): void
    {
        $listing = MarketplaceListing::create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'price_per_unit' => 100,
            'status' => 'active',
        ]);

        // Remove items (as if they were taken from inventory for the listing)
        Player_Item::where('player_id', $this->seller->id)
            ->where('item_id', $this->item->id)
            ->update(['quantity' => 5]);

        $response = $this->deleteJson("/api/marketplace/{$listing->id}/cancel", [
            'seller_id' => $this->seller->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Listing cancelled successfully',
            ]);

        $this->assertDatabaseHas('marketplace_listings', [
            'id' => $listing->id,
            'status' => 'cancelled',
        ]);
    }

    #[Test]
    public function buyer_without_gold_cannot_purchase(): void
    {
        Sanctum::actingAs($this->buyer);
        // Remove buyer's gold
        $this->buyer->resources()->where('resource_type', 'gold')->update(['quantity' => 0]);

        $listing = MarketplaceListing::create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
            'price_per_unit' => 1000,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/marketplace/{$listing->id}/purchase", [
            'buyer_id' => $this->buyer->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have enough gold.',
            ]);
    }
}
