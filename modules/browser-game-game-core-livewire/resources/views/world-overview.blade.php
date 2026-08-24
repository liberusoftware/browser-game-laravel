<section aria-labelledby="browser-game-world-heading">
    <h2 id="browser-game-world-heading">{{ $overview['world']->name }}</h2>
    <p>Status: {{ $overview['world']->status }}</p>
    @if ($overview['maintenance']?->status === 'active')
        <p role="status">{{ $overview['maintenance']->message ?: 'Maintenance is active.' }}</p>
    @endif
</section>
