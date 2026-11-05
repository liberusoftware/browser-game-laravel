<x-filament-panels::page>
    <x-filament::section heading="Choose your next chapter" description="Start with a quest that interests you. Each card shows what you can earn.">
        <x-filament::button tag="a" :href="\App\Filament\App\Pages\AdventureGuide::getUrl(panel: 'app')" color="gray" size="sm">New here? Read the adventure guide</x-filament::button>
    </x-filament::section>
    @unless ($player)
        <x-filament::section heading="Your character is not ready yet" description="You can explore the available quests now. Finish account setup to prepare your character. If player registration is closed, contact your game administrator.">
            <x-filament::button tag="a" :href="\App\Filament\App\Pages\AccountSetup::getUrl(panel: 'app')" size="sm">Set up my character</x-filament::button>
        </x-filament::section>
    @endunless
    @error('adventure')
        <p role="alert" class="rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">{{ $message }}</p>
    @enderror
    <div class="adventure-paths">
        @forelse ($quests as $quest)
            <article class="adventure-path" wire:key="quest-{{ $quest->id }}">
                <div class="flex items-center justify-between gap-2">
                    <x-filament::icon icon="heroicon-o-map" class="h-6 w-6 text-primary-500" />
                    @if ($statuses->has($quest->id))
                        <x-filament::badge :color="$statuses[$quest->id] === 'completed' ? 'success' : 'info'">{{ $statuses[$quest->id] === 'completed' ? 'Completed' : 'In progress' }}</x-filament::badge>
                    @endif
                </div>
                @php
                    $chapter = config('starter-adventure.quests')[$quest->starter_key] ?? null;
                    $locked = $chapter && $chapter['requires'] && ! in_array($chapter['requires'], $completedStarterKeys, true);
                @endphp
                @if ($chapter)<x-filament::badge color="gray">Dawnvale starter adventure</x-filament::badge>@endif
                <h2>{{ $quest->name }}</h2><p>{{ $quest->description }}</p>
                <div class="flex flex-wrap gap-2">
                    <x-filament::badge color="warning">{{ number_format($quest->experience_reward) }} XP</x-filament::badge>
                    @if ($quest->itemReward)<x-filament::badge color="gray">{{ $quest->itemReward->name }}</x-filament::badge>@endif
                </div>
                @if ($locked)
                    <p>Unlock by completing {{ config('starter-adventure.quests')[$chapter['requires']]['name'] }}.</p>
                @elseif ($player && $chapter && ($statuses[$quest->id] ?? null) === 'in-progress')
                    <p class="font-semibold">What will you do?</p>
                    @foreach ($chapter['choices'] as $choice => $label)
                        <x-filament::button wire:key="choice-{{ $quest->id }}-{{ $choice }}" wire:click="completeAdventure({{ $quest->id }}, '{{ $choice }}')" wire:loading.attr="disabled" wire:target="completeAdventure" color="gray" size="sm">{{ $label }}</x-filament::button>
                    @endforeach
                @elseif ($player && ! $statuses->has($quest->id))
                    <x-filament::button wire:click="accept({{ $quest->id }})" wire:loading.attr="disabled" wire:target="accept" size="sm">Accept quest</x-filament::button>
                @endif
            </article>
        @empty
            <x-filament::section heading="New adventures are on the way" description="There are no quests in this world yet. Return soon, or explore the beginner guide while your game administrator prepares the first chapter." />
        @endforelse
    </div>
    {{ $quests->links() }}
</x-filament-panels::page>
