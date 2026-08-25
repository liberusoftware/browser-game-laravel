<div class="space-y-3">
    <div class="text-sm text-gray-500">Inventory ({{ $items->count() }} / {{ $maxSlots }})</div>
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($items as $item)
            <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="font-medium">{{ $item->item->name }}</div>
                <div class="text-sm text-gray-500">Quantity: {{ $item->quantity }}</div>
            </div>
        @empty
            <div class="text-sm text-gray-500">Your inventory is empty.</div>
        @endforelse
    </div>
</div>
