<div class="bg-white shadow-lg rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Guild Panel</h2>

    <!-- Player's Guilds -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            My Guilds ({{ count($playerGuilds) }})
        </h3>
        
        @if(count($playerGuilds) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($playerGuilds as $guild)
                    <div class="border-l-4 border-blue-500 bg-blue-50 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <h4 class="font-bold text-lg text-gray-800">{{ $guild['name'] }}</h4>
                                <p class="text-sm text-gray-600">{{ $guild['description'] }}</p>
                            </div>
                            @if(isset($guild['pivot']) && isset($guild['pivot']['role']))
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded ml-2">
                                    {{ ucfirst($guild['pivot']['role']) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button wire:click="selectGuild({{ $guild['id'] }})" 
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded transition">
                                View Members
                            </button>
                            <button wire:click="leaveGuild({{ $guild['id'] }})" 
                                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded transition">
                                Leave Guild
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">You are not in any guilds. Join one below!</p>
        @endif
    </div>

    <!-- Guild Members (if guild selected) -->
    @if($selectedGuild && count($guildMembers) > 0)
        <div class="mb-8 bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Guild Members ({{ count($guildMembers) }})
            </h3>
            <div class="space-y-2">
                @foreach($guildMembers as $member)
                    <div class="flex justify-between items-center bg-white rounded p-3 border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($member['username'], 0, 1) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">{{ $member['username'] }}</div>
                                <div class="text-sm text-gray-500">Level {{ $member['level'] }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded">
                                {{ ucfirst($member['role']) }}
                            </span>
                            <span class="text-xs text-gray-500">
                                Joined {{ \Carbon\Carbon::parse($member['joined_at'])->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Available Guilds -->
    <div>
        <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Available Guilds ({{ count($availableGuilds) }})
        </h3>
        
        @if(count($availableGuilds) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($availableGuilds as $guild)
                    <div class="border border-gray-200 hover:border-green-400 bg-white rounded-lg p-4 transition">
                        <h4 class="font-bold text-lg text-gray-800 mb-2">{{ $guild['name'] }}</h4>
                        <p class="text-sm text-gray-600 mb-3">{{ $guild['description'] }}</p>
                        <button wire:click="joinGuild({{ $guild['id'] }})" 
                                class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded transition w-full">
                            Join Guild
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No available guilds to join.</p>
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
    <div wire:poll.15s="refreshGuilds"></div>
</div>
