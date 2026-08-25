<section aria-labelledby="browser-game-world-heading">
    <h2 id="browser-game-world-heading">World</h2>
    @if($message)<p role="status">{{ $message }}</p>@endif
    <form wire:submit="travel('{{ $originId }}', '{{ $destinationId }}')">
        <label>Origin <select wire:model="originId" required><option value="">Choose origin</option>@foreach($entities as $entity)<option value="{{ $entity->getKey() }}">{{ $entity->name }} ({{ $entity->kind }})</option>@endforeach</select></label>
        <label>Destination <select wire:model="destinationId" required><option value="">Choose destination</option>@foreach($entities as $entity)<option value="{{ $entity->getKey() }}">{{ $entity->name }} ({{ $entity->kind }})</option>@endforeach</select></label>
        <button type="submit">Travel</button>
    </form>
    @forelse ($entities as $entity)
        <article><h3>{{ $entity->name }}</h3><p>{{ $entity->kind }}</p>@if($entity->unlock_key)<button type="button" wire:click="unlock('{{ $entity->getKey() }}')">Unlock</button>@endif</article>
    @empty
        <p role="status">No world content is available.</p>
    @endforelse
</section>
