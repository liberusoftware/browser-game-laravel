<section aria-labelledby="browser-game-character-heading">
    <h2 id="browser-game-character-heading">{{ $character->name }}</h2>
    <p>{{ $character->race }} {{ $character->class }}</p>
    @if ($statusMessage)<p role="status">{{ $statusMessage }}</p>@endif
    <dl>
        <dt>Level</dt><dd>{{ $character->level }}</dd>
        <dt>Experience</dt><dd>{{ $character->experience }}</dd>
        <dt>Health</dt><dd>{{ $character->health }} / {{ $character->max_health }}</dd>
        <dt>Mana</dt><dd>{{ $character->mana }} / {{ $character->max_mana }}</dd>
        <dt>Stat points</dt><dd>{{ $character->stat_points }}</dd>
    </dl>
    <form wire:submit="spendStats">
        <fieldset>
            <legend>Spend statistics</legend>
            @foreach(['strength', 'defense', 'agility', 'intelligence'] as $stat)
                <label>{{ ucfirst($stat) }} <input type="number" min="0" wire:model="statistics.{{ $stat }}"></label>
            @endforeach
            <button type="submit">Apply statistics</button>
        </fieldset>
    </form>
    <form wire:submit="respec">
        <label>Skill points <input type="number" min="0" wire:model="skills.general"></label>
        <button type="submit">Respec skills</button>
    </form>
    <form wire:submit="allocateSkills">
        <fieldset>
            <legend>Allocate skill points</legend>
            <label>General <input type="number" min="0" wire:model="skillAllocation.general"></label>
            <button type="submit">Allocate skills</button>
        </fieldset>
    </form>
    <form wire:submit="updateVitals">
        <fieldset>
            <legend>Update vitals</legend>
            <label>Health <input type="number" min="0" wire:model="health"></label>
            <label>Mana <input type="number" min="0" wire:model="mana"></label>
            <button type="submit">Save vitals</button>
        </fieldset>
    </form>
</section>
