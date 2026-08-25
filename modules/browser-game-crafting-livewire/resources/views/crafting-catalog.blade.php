<section aria-labelledby="browser-game-crafting-heading">
    <h2 id="browser-game-crafting-heading">Crafting</h2>
    @if ($statusMessage)<p role="status">{{ $statusMessage }}</p>@endif
    <label>Quantity <input type="number" min="1" max="1000" wire:model="quantity"></label>
    <label>Quality <input type="number" min="0" max="100" wire:model="quality"></label>
    @forelse($crafting as $recipe)
        <article wire:key="recipe-{{ $recipe->getKey() }}">
            <h3>{{ $recipe->name }}</h3>
            <p>{{ $recipe->description }}</p>
            <p>Requires level {{ $recipe->min_level }}; success {{ $recipe->success_rate }}%.</p>
            @if($recipe->discovery_requirements)
                <button type="button" wire:click="discover('{{ $recipe->getKey() }}')">Discover recipe</button>
            @endif
            <button type="button" wire:click="queue('{{ $recipe->getKey() }}')">Start crafting</button>
        </article>
    @empty
        <p role="status">No crafting recipes are available.</p>
    @endforelse

    <h3>My crafting queues</h3>
    @forelse($queues as $queue)
        <article wire:key="queue-{{ $queue->getKey() }}">
            <p>{{ $queue->recipe?->name ?? 'Crafting job' }}: {{ $queue->quantity }} item(s), {{ $queue->status }}</p>
            @if ($queue->completes_at)<p>Completes {{ $queue->completes_at->toDateTimeString() }}</p>@endif
            @if ($queue->status === 'queued')
                <button type="button" wire:click="complete('{{ $queue->getKey() }}')">Complete</button>
                <button type="button" wire:click="cancel('{{ $queue->getKey() }}')">Cancel</button>
            @elseif (in_array($queue->status, ['completed', 'failed'], true))
                <button type="button" wire:click="salvage('{{ $queue->getKey() }}')">Salvage</button>
            @endif
        </article>
    @empty
        <p role="status">No crafting queues found.</p>
    @endforelse
</section>
