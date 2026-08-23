<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Leaderboards</h2>

    <!-- Category Tabs -->
    <div class="flex space-x-2 mb-6 overflow-x-auto">
        @foreach($categories as $key => $label)
            <button wire:click="selectCategory('{{ $key }}')" 
                class="px-4 py-2 rounded-lg font-semibold whitespace-nowrap {{ $selectedCategory === $key ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- Leaderboard Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Player</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        @if($selectedCategory === 'level')
                            Level
                        @elseif($selectedCategory === 'pvp_wins')
                            Wins
                        @elseif($selectedCategory === 'quests')
                            Quests
                        @elseif($selectedCategory === 'wealth')
                            Gold
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($leaderboard as $entry)
                    <tr class="{{ $loop->iteration <= 3 ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($loop->iteration === 1)
                                    <span class="text-2xl">🥇</span>
                                @elseif($loop->iteration === 2)
                                    <span class="text-2xl">🥈</span>
                                @elseif($loop->iteration === 3)
                                    <span class="text-2xl">🥉</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400 font-bold">{{ $entry->rank }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $entry->player->username }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white font-bold">
                                {{ number_format($entry->value) }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            No leaderboard data available yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
