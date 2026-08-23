<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Crafting Workshop</h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Known Recipes -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Your Recipes</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($knownRecipes as $recipe)
                    <div wire:click="selectRecipe({{ $recipe->id }})" 
                        class="cursor-pointer p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 {{ $selectedRecipe && $selectedRecipe->id == $recipe->id ? 'ring-2 ring-blue-500' : '' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">{{ $recipe->name }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $recipe->description }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Success: {{ $recipe->success_rate }}%</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">You haven't learned any recipes yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Crafting Details -->
        <div>
            @if($selectedRecipe)
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ $selectedRecipe->name }}
                    </h3>
                    
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $selectedRecipe->description }}</p>
                    
                    <!-- Result -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Result:</h4>
                        <div class="flex items-center space-x-2">
                            <span class="text-gray-900 dark:text-white">{{ $selectedRecipe->resultItem->name }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">(x{{ $selectedRecipe->result_quantity }})</span>
                        </div>
                    </div>

                    <!-- Materials Required -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Materials Required:</h4>
                        <div class="space-y-1">
                            @foreach($selectedRecipe->materials as $material)
                                @php
                                    $playerItem = $player->playerItems()->where('item_id', $material->item_id)->first();
                                    $hasEnough = $playerItem && $playerItem->quantity >= $material->quantity;
                                @endphp
                                <div class="flex items-center justify-between {{ $hasEnough ? 'text-green-600' : 'text-red-600' }}">
                                    <span>{{ $material->item->name }}</span>
                                    <span>{{ $playerItem->quantity ?? 0 }} / {{ $material->quantity }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Crafting Info -->
                    <div class="mb-4 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Success Rate:</span>
                            <span>{{ $selectedRecipe->success_rate }}%</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Min Level:</span>
                            <span>{{ $selectedRecipe->min_level }}</span>
                        </div>
                    </div>

                    <!-- Craft Button -->
                    <button wire:click="craftItem" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Craft Item
                    </button>

                    <!-- Crafting Result -->
                    @if($craftingResult)
                        <div class="mt-4 p-3 rounded {{ $craftingResult['success'] ? 'bg-green-100 dark:bg-green-800' : 'bg-red-100 dark:bg-red-800' }}">
                            <p class="{{ $craftingResult['success'] ? 'text-green-800 dark:text-green-100' : 'text-red-800 dark:text-red-100' }}">
                                {{ $craftingResult['message'] }}
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-700 p-8 rounded-lg text-center">
                    <p class="text-gray-500 dark:text-gray-400">Select a recipe to begin crafting</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Available Recipes to Learn -->
    @if($availableRecipes->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Available Recipes</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($availableRecipes as $recipe)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h4 class="font-bold text-gray-900 dark:text-white">{{ $recipe->name }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $recipe->description }}</p>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Required Level: {{ $recipe->min_level }}
                        </div>
                        <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2">
                            Find this recipe in quests or shops!
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
