<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MarketplaceListing;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    /**
     * Create a new marketplace listing.
     */
    public function createListing(Player $seller, Item $item, int $quantity, int $pricePerUnit): ?MarketplaceListing
    {
        return DB::transaction(function () use ($seller, $item, $quantity, $pricePerUnit) {
            $playerItem = $seller->playerItems()
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if (! $playerItem || $playerItem->quantity < $quantity) {
                return;
            }

            // Remove items from inventory
            $newQuantity = $playerItem->quantity - $quantity;
            if ($newQuantity <= 0) {
                $playerItem->delete();
            } else {
                $playerItem->update(['quantity' => $newQuantity]);
            }

            // Create listing
            return MarketplaceListing::create([
                'seller_id' => $seller->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'price_per_unit' => $pricePerUnit,
                'status' => 'active',
            ]);
        });
    }

    /**
     * Purchase an item from the marketplace.
     */
    public function purchaseListing(Player $buyer, MarketplaceListing $listing): array
    {
        return DB::transaction(function () use ($buyer, $listing) {
            $listing = MarketplaceListing::query()->lockForUpdate()->findOrFail($listing->id);

            if ($listing->status !== 'active') {
                return ['success' => false, 'message' => 'This listing is no longer available.'];
            }

            if ($listing->seller_id === $buyer->id) {
                return ['success' => false, 'message' => 'You cannot buy your own listing.'];
            }

            $totalPrice = $listing->price_per_unit * $listing->quantity;
            $buyerGold = $buyer->resources()
                ->where('resource_type', 'gold')
                ->lockForUpdate()
                ->first();

            if (! $buyerGold || $buyerGold->quantity < $totalPrice) {
                return ['success' => false, 'message' => 'You do not have enough gold.'];
            }

            // Deduct gold from buyer
            $buyerGold->decrement('quantity', $totalPrice);

            // Add gold to seller
            $seller = $listing->seller;
            $sellerGold = $seller->resources()->where('resource_type', 'gold')->first();
            if ($sellerGold) {
                $sellerGold->increment('quantity', $totalPrice);
            } else {
                $seller->resources()->create([
                    'resource_type' => 'gold',
                    'quantity' => $totalPrice,
                ]);
            }

            // Add item to buyer's inventory
            $buyerItem = $buyer->playerItems()->where('item_id', $listing->item_id)->first();
            if ($buyerItem) {
                $buyerItem->increment('quantity', $listing->quantity);
            } else {
                $buyer->playerItems()->create([
                    'item_id' => $listing->item_id,
                    'quantity' => $listing->quantity,
                ]);
            }

            // Update listing
            $listing->update([
                'status' => 'sold',
                'buyer_id' => $buyer->id,
                'sold_at' => now(),
            ]);

            return ['success' => true, 'message' => 'Purchase successful!'];
        });
    }

    /**
     * Cancel a listing.
     */
    public function cancelListing(Player $seller, MarketplaceListing $listing): bool
    {
        return DB::transaction(function () use ($seller, $listing) {
            $listing = MarketplaceListing::query()->lockForUpdate()->findOrFail($listing->id);

            if ($listing->seller_id !== $seller->id || $listing->status !== 'active') {
                return false;
            }

            // Return items to seller
            $sellerItem = $seller->playerItems()->where('item_id', $listing->item_id)->first();
            if ($sellerItem) {
                $sellerItem->increment('quantity', $listing->quantity);
            } else {
                $seller->playerItems()->create([
                    'item_id' => $listing->item_id,
                    'quantity' => $listing->quantity,
                ]);
            }

            // Cancel listing
            $listing->update(['status' => 'cancelled']);

            return true;
        });
    }
}
