<?php

namespace App\Livewire;

use App\Models\MarketplaceListing;
use App\Models\Player;
use App\Services\MarketplaceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Marketplace extends Component
{
    use WithPagination;

    public $player;

    public $searchTerm = '';

    public $selectedItem;

    public $sellQuantity = 1;

    public $sellPrice = 100;

    public function mount()
    {
        $this->player = Auth::user()?->player ?? Player::where('email', Auth::user()->email)->first();
    }

    public function selectItemToSell($itemId)
    {
        $playerItem = $this->player->playerItems()->where('item_id', $itemId)->first();
        if ($playerItem) {
            $this->selectedItem = $playerItem->item;
            $this->sellQuantity = 1;
            $this->sellPrice = $this->selectedItem->sell_price ?? 100;
        }
    }

    public function createListing()
    {
        if (! $this->selectedItem) {
            return;
        }

        $marketplaceService = app(MarketplaceService::class);
        $listing = $marketplaceService->createListing(
            $this->player,
            $this->selectedItem,
            $this->sellQuantity,
            $this->sellPrice
        );

        if ($listing) {
            $this->dispatch('show-message', message: 'Item listed on marketplace!');
            $this->selectedItem = null;
            $this->player->refresh();
        } else {
            $this->dispatch('show-error', message: 'Failed to create listing.');
        }
    }

    public function purchaseItem($listingId)
    {
        $listing = MarketplaceListing::find($listingId);

        if (! $listing) {
            return;
        }

        $marketplaceService = app(MarketplaceService::class);
        $result = $marketplaceService->purchaseListing($this->player, $listing);

        if ($result['success']) {
            $this->dispatch('show-message', message: $result['message']);
            $this->player->refresh();
        } else {
            $this->dispatch('show-error', message: $result['message']);
        }
    }

    public function cancelListing($listingId)
    {
        $listing = MarketplaceListing::find($listingId);

        if (! $listing) {
            return;
        }

        $marketplaceService = app(MarketplaceService::class);
        if ($marketplaceService->cancelListing($this->player, $listing)) {
            $this->dispatch('show-message', message: 'Listing cancelled.');
            $this->player->refresh();
        }
    }

    public function render()
    {
        $query = MarketplaceListing::active()
            ->with(['item', 'seller'])
            ->where('seller_id', '!=', $this->player->id);

        if ($this->searchTerm) {
            $query->whereHas('item', function ($q) {
                $q->where('name', 'like', '%'.$this->searchTerm.'%');
            });
        }

        return view('livewire.marketplace', [
            'listings' => $query->latest()->paginate(10),
            'myListings' => $this->player->sellerListings()
                ->where('status', 'active')
                ->with('item')
                ->latest()
                ->get(),
            'playerInventory' => $this->player->playerItems()->with('item')->get(),
        ]);
    }
}
