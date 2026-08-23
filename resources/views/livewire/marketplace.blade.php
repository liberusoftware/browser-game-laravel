<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Marketplace</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Active Listings -->
        <div class="lg:col-span-2">
            <div class="mb-4">
                <input type="text" wire:model.live="searchTerm" placeholder="Search items..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($listings as $listing)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white">{{ $listing->item->name }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $listing->item->description }}</p>
                                <div class="mt-2 flex items-center space-x-4 text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        Seller: {{ $listing->seller->username }}
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        Quantity: {{ $listing->quantity }}
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        Rarity: <span class="font-semibold">{{ ucfirst($listing->item->rarity) }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ $listing->price_per_unit * $listing->quantity }}g
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $listing->price_per_unit }}g each
                                </div>
                                <button wire:click="purchaseItem({{ $listing->id }})" 
                                    class="mt-2 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Buy
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">No items available on the marketplace.</p>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $listings->links() }}
            </div>
        </div>

        <!-- Sell Items Sidebar -->
        <div>
            <!-- Sell Item Form -->
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Sell an Item</h3>
                
                @if($selectedItem)
                    <div class="space-y-3">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $selectedItem->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedItem->description }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                            <input type="number" wire:model="sellQuantity" min="1" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price per Unit</label>
                            <input type="number" wire:model="sellPrice" min="1" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                        </div>
                        
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total: {{ $sellPrice * $sellQuantity }}g
                        </div>
                        
                        <button wire:click="createListing" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            List Item
                        </button>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Select an item from your inventory below</p>
                @endif
            </div>

            <!-- Your Inventory -->
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Your Inventory</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($playerInventory as $playerItem)
                        <div wire:click="selectItemToSell({{ $playerItem->item_id }})" 
                            class="cursor-pointer p-2 bg-white dark:bg-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-500">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $playerItem->item->name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">x{{ $playerItem->quantity }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">Your inventory is empty</p>
                    @endforelse
                </div>
            </div>

            <!-- Your Active Listings -->
            @if($myListings->count() > 0)
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Your Listings</h3>
                    <div class="space-y-2">
                        @foreach($myListings as $listing)
                            <div class="p-2 bg-white dark:bg-gray-600 rounded">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $listing->item->name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">
                                            {{ $listing->quantity }}x @ {{ $listing->price_per_unit }}g
                                        </span>
                                    </div>
                                    <button wire:click="cancelListing({{ $listing->id }})" 
                                        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
