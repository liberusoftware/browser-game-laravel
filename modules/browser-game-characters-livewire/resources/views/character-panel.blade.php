<section aria-labelledby="browser-game-character-heading">
    <h2 id="browser-game-character-heading">{{ $character->name }}</h2>
    <p>{{ $character->race }} {{ $character->class }}</p>
    <dl>
        <dt>Level</dt><dd>{{ $character->level }}</dd>
        <dt>Experience</dt><dd>{{ $character->experience }}</dd>
        <dt>Health</dt><dd>{{ $character->health }} / {{ $character->max_health }}</dd>
    </dl>
</section>
