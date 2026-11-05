<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-sparkles" heading="Build a world worth returning to" description="Configure starting resources, progression, and social play in three steps.">
        <p class="text-sm text-gray-600 dark:text-gray-400">These settings apply to the whole game. Saving updates configuration; it does not reset existing characters or create game content.</p>
    </x-filament::section>
    <form wire:submit="save">
        {{ $this->form }}
    </form>
    <x-filament::section heading="Ready to play: the Dawnvale starter adventure" :description="'Install '.count(config('starter-adventure.quests')).' linked story quests, '.count(config('starter-adventure.items')).' example items, and starter kits for '.count(config('starter-adventure.classes')).' character classes, plus '.count(config('arena.npcs')).' NPC opponents.'">
        <p class="text-sm text-gray-600 dark:text-gray-400">Safe to run again: existing content edits, inventory, and quest progress are preserved. No demo login accounts are created.</p>
        <x-filament::button class="mt-4" wire:click="installStarterAdventure" wire:loading.attr="disabled" wire:target="installStarterAdventure" icon="heroicon-o-sparkles">Install starter adventure</x-filament::button>
    </x-filament::section>
    <x-filament::section heading="Next: bring your world to life" collapsible>
        <ol class="list-decimal space-y-3 pl-5 text-sm text-gray-600 dark:text-gray-400">
            <li>Add a welcoming quest with a clear objective and a useful reward.</li>
            <li>Create starter equipment and resources in Game Content.</li>
            <li>Open the player panel and test the first adventure before inviting players.</li>
        </ol>
        <x-filament::button tag="a" :href="route('filament.app.pages.dashboard')" class="mt-4" color="gray">Open player panel</x-filament::button>
    </x-filament::section>
</x-filament-panels::page>
