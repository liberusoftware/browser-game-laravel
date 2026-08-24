<section aria-labelledby="browser-game-crafting-heading">
    <h2 id="browser-game-crafting-heading">Crafting</h2>
    <label>Quantity <input type="number" min="1" max="1000" wire:model="quantity"></label>
    <label>Quality <input type="number" min="0" max="100" wire:model="quality"></label>
    @forelse($crafting as $recipe)
        <article wire:key="recipe-{{ $recipe->getKey() }}">
            <h3>{{ $recipe->name }}</h3>
            <p>{{ $recipe->description }}</p>
            <p>Requires level {{ $recipe->min_level }}; success {{ $recipe->success_rate }}%.</p>
            <button type="button" wire:click="queue('{{ $recipe->getKey() }}')">Start crafting</button>
        </article>
    @empty
        <p role="status">No crafting recipes are available.</p>
    @endforelse
</section>
