<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-sparkles" heading="Your first adventure starts here" description="A two-minute field guide. Explore at your own pace and return whenever you need a hand.">
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ auth()->user()->game_guide_completed_at ? 'Guide completed — you can still revisit every tip below.' : 'Read these four steps, then mark the guide complete. Your progress is saved to your account.' }}</p>
    </x-filament::section>
    <x-filament::section heading="Choose your play style" description="Your class sets your starting stats and weapon. Every class can finish the Dawnvale adventure.">
        <div class="adventure-paths">
            @foreach (config('starter-adventure.classes') as $key => $class)
                <article class="adventure-path" wire:key="class-{{ $key }}">
                    <h2>{{ $class['name'] }}</h2><p>{{ $class['description'] }}</p>
                    <p>Health {{ $class['stats']['health'] }} · Mana {{ $class['stats']['mana'] }}<br>Strength {{ $class['stats']['strength'] }} · Agility {{ $class['stats']['agility'] }}<br>Defense {{ $class['stats']['defense'] }} · Intelligence {{ $class['stats']['intelligence'] }}</p>
                    <x-filament::badge color="gray">{{ config('starter-adventure.items')[$class['weapon']]['name'] }}</x-filament::badge>
                </article>
            @endforeach
        </div>
    </x-filament::section>
    <div class="adventure-paths">
        @foreach ([
            ['01', 'Make it yours', 'Choose your display name, character class, and team in account setup. Your team is your account workspace; it is separate from an in-game guild.'],
            ['02', 'Choose a quest', 'Open the quest journal. Read the objective and compare XP and item rewards. Begin with A New Arrival in Dawnvale. Accept it, read the scene, and choose your response to earn rewards and unlock the next chapter.'],
            ['03', 'Grow stronger', 'Your overview shows your current level and experience. Quest rewards help you progress; check your active quests to decide what to tackle next.'],
            ['04', 'Check your pack', 'Your inventory shows collected items and quantities. Check your rewards after an adventure and plan which equipment you want to earn next.'],
        ] as [$number, $title, $description])
            <article wire:key="guide-{{ $number }}" class="adventure-path">
                <span class="adventure-step">{{ $number }}</span><h2>{{ $title }}</h2><p>{{ $description }}</p>
            </article>
        @endforeach
    </div>
    <div class="flex flex-wrap gap-3">
        <x-filament::button wire:click="complete" wire:loading.attr="disabled" wire:target="complete" icon="heroicon-o-check-circle">{{ auth()->user()->game_guide_completed_at ? 'Back to my adventure' : 'Got it — let’s explore' }}</x-filament::button>
        <x-filament::button tag="a" :href="\App\Filament\App\Pages\QuestJournal::getUrl(panel: 'app')" color="gray">Open quest journal</x-filament::button>
        <x-filament::button tag="a" :href="\App\Filament\App\Pages\AccountSetup::getUrl(panel: 'app')" color="gray">Account setup</x-filament::button>
    </div>
</x-filament-panels::page>
