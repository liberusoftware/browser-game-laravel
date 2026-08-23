<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Combat Arena</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Player Stats -->
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Your Stats</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Health:</span>
                    <span class="font-bold {{ $player->health < 30 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $player->health }} / {{ $player->max_health }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Mana:</span>
                    <span class="font-bold text-blue-600">{{ $player->mana }} / {{ $player->max_mana }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Strength:</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $player->strength }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Defense:</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $player->defense }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Agility:</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $player->agility }}</span>
                </div>
            </div>
            
            <div class="mt-4">
                <button wire:click="heal" 
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Heal (50 Gold)
                </button>
            </div>
        </div>

        <!-- Battle Controls -->
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Battle</h3>
            
            @if(!$battleInProgress)
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Opponent Level
                        </label>
                        <input type="number" wire:model="opponentLevel" min="1" :max="{{ $player->level + 5 }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                    </div>
                    
                    <button wire:click="startPvEBattle" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded">
                        Start Battle!
                    </button>
                </div>
            @else
                <div class="space-y-4">
                    <div class="bg-white dark:bg-gray-600 p-4 rounded">
                        <h4 class="font-bold text-lg text-gray-900 dark:text-white mb-2">
                            Battle vs {{ $battle->opponent_name }} (Lvl {{ $battle->opponent_level }})
                        </h4>
                        
                        @if($battle->winner_id == $player->id)
                            <div class="bg-green-100 dark:bg-green-800 p-3 rounded mb-3">
                                <p class="text-green-800 dark:text-green-100 font-bold">Victory!</p>
                                <p class="text-sm text-green-700 dark:text-green-200">
                                    +{{ $battle->experience_gained }} XP, +{{ $battle->gold_gained }} Gold
                                </p>
                            </div>
                        @else
                            <div class="bg-red-100 dark:bg-red-800 p-3 rounded mb-3">
                                <p class="text-red-800 dark:text-red-100 font-bold">Defeat</p>
                            </div>
                        @endif

                        <div class="max-h-64 overflow-y-auto space-y-2">
                            @foreach($battle->battle_log as $log)
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-semibold">Round {{ $log['round'] }}:</span>
                                    <span class="{{ $log['actor'] == 'attacker' ? 'text-blue-600' : 'text-red-600' }}">
                                        {{ $log['actor'] == 'attacker' ? 'You' : $battle->opponent_name }}
                                    </span>
                                    dealt {{ $log['damage'] }} damage
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <button wire:click="viewBattleLog" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        New Battle
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Battles -->
    <div class="mt-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Recent Battles</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Opponent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Result</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rewards</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentBattles as $battle)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $battle->opponent_name ?? ($battle->defender->username ?? 'Unknown') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ strtoupper($battle->battle_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($battle->winner_id == $player->id)
                                    <span class="text-green-600 font-bold">Win</span>
                                @else
                                    <span class="text-red-600 font-bold">Loss</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $battle->experience_gained }} XP, {{ $battle->gold_gained }} Gold
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $battle->completed_at?->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No battles yet. Start your first battle!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
