<div class="bg-white shadow-lg rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Player Dashboard</h2>
    
    @if($player)
        <!-- Player Stats Card -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-3xl font-bold">{{ $player->username }}</h3>
                    <p class="text-blue-100">{{ $player->email }}</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold">{{ $player->level }}</div>
                    <div class="text-sm text-blue-100">Level</div>
                </div>
            </div>
            
            <!-- Experience Bar -->
            <div class="mt-4">
                <div class="flex justify-between text-sm mb-1">
                    <span>Experience</span>
                    <span>{{ $player->experience }} / {{ $nextLevelXp }} XP</span>
                </div>
                <div class="w-full bg-blue-900 rounded-full h-4 overflow-hidden">
                    <div class="bg-yellow-400 h-full rounded-full transition-all duration-500" 
                         style="width: {{ $experiencePercentage }}%"></div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Active Quests -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-600 font-medium">Active Quests</p>
                        <p class="text-2xl font-bold text-green-800">
                            {{ $player->activeQuests ? $player->activeQuests->count() : 0 }}
                        </p>
                    </div>
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>

            <!-- Inventory Items -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-purple-600 font-medium">Inventory Items</p>
                        <p class="text-2xl font-bold text-purple-800">
                            {{ $player->items ? $player->items->count() : 0 }}
                        </p>
                    </div>
                    <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>

            <!-- Guilds -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-orange-600 font-medium">Guilds</p>
                        <p class="text-2xl font-bold text-orange-800">
                            {{ $player->guilds ? $player->guilds->count() : 0 }}
                        </p>
                    </div>
                    <svg class="w-12 h-12 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Real-time Update Indicator -->
        <div wire:poll.5s="refreshPlayer" class="text-center text-sm text-gray-500">
            <span class="inline-flex items-center">
                <svg class="animate-pulse w-2 h-2 mr-2 text-green-500" fill="currentColor" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="4"/>
                </svg>
                Live Updates Active
            </span>
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <p>No player data available</p>
        </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
</div>
