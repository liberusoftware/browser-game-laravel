<div class="bg-white shadow-lg rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Player Inventory</h2>

    <!-- Items Section -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            Items ({{ count($inventory) }})
        </h3>
        
        @if(count($inventory) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($inventory as $item)
                    @php
                        $rarityColors = [
                            'common' => 'border-gray-300 bg-gray-50',
                            'uncommon' => 'border-green-300 bg-green-50',
                            'rare' => 'border-blue-300 bg-blue-50',
                            'epic' => 'border-purple-300 bg-purple-50',
                            'legendary' => 'border-yellow-400 bg-yellow-50',
                        ];
                        $cardClass = $rarityColors[$item['rarity']] ?? 'border-gray-300 bg-gray-50';
                    @endphp
                    <div class="border-2 {{ $cardClass }} rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-gray-800">{{ $item['name'] }}</h4>
                            <span class="px-2 py-1 bg-white rounded text-sm font-semibold">
                                x{{ $item['quantity'] }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">{{ $item['description'] }}</p>
                        <div class="flex justify-between items-center">
                            <div class="space-y-1">
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded">
                                    {{ ucfirst($item['type']) }}
                                </span>
                                <span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded">
                                    {{ ucfirst($item['rarity']) }}
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="useItem({{ $item['id'] }})" 
                                        class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded transition"
                                        title="Use Item">
                                    Use
                                </button>
                                <button wire:click="dropItem({{ $item['id'] }})" 
                                        class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded transition"
                                        title="Drop Item">
                                    Drop
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p>Your inventory is empty</p>
                <p class="text-sm">Complete quests to earn items!</p>
            </div>
        @endif
    </div>

    <!-- Resources Section -->
    <div>
        <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Resources ({{ count($resources) }})
        </h3>
        
        @if(count($resources) > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($resources as $resource)
                    <div class="border border-orange-200 bg-orange-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-orange-700">{{ $resource['quantity'] }}</div>
                        <div class="text-sm text-orange-600 font-medium capitalize">
                            {{ str_replace('_', ' ', $resource['resource_type']) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No resources available.</p>
        @endif
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif
    @if (session()->has('info'))
        <div class="mt-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('info') }}</span>
        </div>
    @endif

    <!-- Real-time updates -->
    <div wire:poll.10s="refreshInventory"></div>
</div>
