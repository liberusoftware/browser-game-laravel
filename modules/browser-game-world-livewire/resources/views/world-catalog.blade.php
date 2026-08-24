<section aria-labelledby="browser-game-world-heading">
    <h2 id="browser-game-world-heading">World</h2>
    @forelse ($entities as $entity)
        <article><h3>{{ $entity->name }}</h3><p>{{ $entity->kind }}</p></article>
    @empty
        <p role="status">No world content is available.</p>
    @endforelse
</section>
