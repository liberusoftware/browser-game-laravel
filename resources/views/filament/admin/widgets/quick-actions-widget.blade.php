<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($this->getActions() as $action)
        <a href="{{ $action['url'] }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="font-medium text-gray-950 dark:text-white">{{ $action['label'] }}</span>
        </a>
    @endforeach
</div>
