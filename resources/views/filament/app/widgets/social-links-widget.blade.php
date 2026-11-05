<x-filament-widgets::widget>
    <x-filament::section heading="Around the campfire" description="Keep in touch with your game community.">
        <div class="flex flex-wrap gap-3">
            @forelse ($links as $label => $url)
                <x-filament::link wire:key="community-{{ $label }}" :href="$url" target="_blank" rel="noopener noreferrer" icon="heroicon-o-arrow-top-right-on-square">{{ $label }} <span class="sr-only">(opens in a new tab)</span></x-filament::link>
            @empty
                <p class="text-sm text-gray-600 dark:text-gray-400">Community links will appear here when your game administrator adds them.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
