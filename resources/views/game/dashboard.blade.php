<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Game Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <!-- Daily Reward -->
            <div>
                @livewire('daily-reward-claim')
            </div>

            <!-- Player Dashboard -->
            <div>
                @livewire('player-dashboard')
            </div>

            <!-- Combat Arena -->
            <div>
                @livewire('combat-arena')
            </div>

            <!-- Quests and Inventory Side by Side -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    @livewire('quest-board')
                </div>
                <div>
                    @livewire('player-inventory')
                </div>
            </div>

            <!-- Crafting Workshop -->
            <div>
                @livewire('crafting-workshop')
            </div>

            <!-- Marketplace -->
            <div>
                @livewire('marketplace')
            </div>

            <!-- Leaderboard -->
            <div>
                @livewire('leaderboard-panel')
            </div>

            <!-- Guild Panel -->
            <div>
                @livewire('guild-panel')
            </div>
        </div>
    </div>
</x-app-layout>
