<div class="bg-gradient-to-r from-yellow-400 to-orange-500 dark:from-yellow-600 dark:to-orange-700 overflow-hidden shadow-xl sm:rounded-lg p-6">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <h3 class="text-2xl font-bold text-white mb-2">Daily Reward</h3>
            
            @if($canClaim)
                <p class="text-yellow-100 mb-4">🎁 Your daily reward is ready to claim!</p>
                <button wire:click="claimReward" 
                    class="bg-white hover:bg-gray-100 text-yellow-600 font-bold py-3 px-6 rounded-lg shadow-lg transform hover:scale-105 transition">
                    Claim Reward!
                </button>
            @else
                <p class="text-yellow-100 mb-2">✅ Already claimed today. Come back tomorrow!</p>
                @if($lastReward)
                    <p class="text-sm text-yellow-200">
                        Last reward: +{{ $lastReward->gold_rewarded }}g, +{{ $lastReward->experience_rewarded }} XP
                    </p>
                @endif
            @endif
        </div>

        <div class="text-right">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-lg">
                <div class="text-4xl font-bold text-orange-600 dark:text-orange-400">
                    {{ $currentStreak }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Day Streak
                </div>
            </div>
            
            <div class="mt-3 text-sm text-yellow-100">
                <p>🔥 7-day bonus: 5× Health Potions</p>
                <p>💎 30-day bonus: Legendary Item!</p>
            </div>
        </div>
    </div>
</div>
