<div class="bg-white shadow-lg rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Quest Board</h2>

    <!-- Active Quests -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Active Quests ({{ count($activeQuests) }})
        </h3>
        
        @if(count($activeQuests) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($activeQuests as $quest)
                    <div class="border-l-4 border-blue-500 bg-blue-50 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-lg text-gray-800">{{ $quest->name }}</h4>
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">In Progress</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-3">{{ $quest->description }}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-700">
                                <svg class="w-4 h-4 mr-1 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                {{ $quest->experience_reward }} XP
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="completeQuest({{ $quest->id }})" 
                                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded transition">
                                    Complete
                                </button>
                                <button wire:click="abandonQuest({{ $quest->id }})" 
                                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded transition">
                                    Abandon
                                </button>
                            </div>
                        </div>
                        @if($quest->itemReward)
                            <div class="mt-2 text-sm text-purple-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                                Reward: {{ $quest->itemReward->name }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No active quests. Accept a quest to get started!</p>
        @endif
    </div>

    <!-- Available Quests -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Available Quests ({{ count($availableQuests) }})
        </h3>
        
        @if(count($availableQuests) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($availableQuests as $quest)
                    <div class="border border-gray-200 hover:border-green-400 bg-white rounded-lg p-4 transition">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-lg text-gray-800">{{ $quest->name }}</h4>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded">Available</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-3">{{ $quest->description }}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-700">
                                <svg class="w-4 h-4 mr-1 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                {{ $quest->experience_reward }} XP
                            </div>
                            <button wire:click="acceptQuest({{ $quest->id }})" 
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded transition">
                                Accept Quest
                            </button>
                        </div>
                        @if($quest->itemReward)
                            <div class="mt-2 text-sm text-purple-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                                Reward: {{ $quest->itemReward->name }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No available quests at the moment.</p>
        @endif
    </div>

    <!-- Completed Quests -->
    <div>
        <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Completed Quests ({{ count($completedQuests) }})
        </h3>
        
        @if(count($completedQuests) > 0)
            <div class="space-y-2">
                @foreach($completedQuests as $quest)
                    <div class="border border-gray-200 bg-gray-50 rounded p-3 flex justify-between items-center opacity-75">
                        <div>
                            <h4 class="font-medium text-gray-700">{{ $quest->name }}</h4>
                            <p class="text-sm text-gray-500">{{ $quest->experience_reward }} XP</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded">Completed</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No completed quests yet.</p>
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

    <!-- Real-time polling -->
    <div wire:poll.10s="refreshQuests"></div>
</div>
