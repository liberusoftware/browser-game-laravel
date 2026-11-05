<x-filament-widgets::widget>
    <x-filament::section heading="Your inventory" :description="$items->count().' / '.$maxSlots.' item slots'" icon="heroicon-o-archive-box">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($items as $item)
                <div wire:key="inventory-{{ $item->id }}" class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <p class="font-semibold text-gray-950 dark:text-white">{{ $item->item?->name ?? 'Unknown item' }}</p>
                    @if ($item->item)
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-filament::badge color="gray">{{ ucfirst($item->item->type) }}</x-filament::badge>
                            <x-filament::badge :color="match ($item->item->rarity) { 'rare' => 'info', 'uncommon' => 'success', default => 'gray' }">{{ ucfirst($item->item->rarity) }}</x-filament::badge>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $item->item->description }}</p>
                    @endif
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quantity: {{ $item->quantity }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-600 dark:text-gray-400">Your pack is ready for its first treasure. Look for quests with item rewards in the quest journal.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
