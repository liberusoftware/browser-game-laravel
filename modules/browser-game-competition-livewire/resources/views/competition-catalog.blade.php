<section aria-labelledby="browser-game-competition-heading">
    <h2 id="browser-game-competition-heading">Competition</h2>
    @if ($error !== '')
        <p role="alert">{{ $error }}</p>
    @endif
    @forelse($competition as $entry)
        <article wire:key="competition-{{ $entry->getKey() }}">
            <h3>{{ $entry->name }}</h3>
            <p>{{ $entry->status }}</p>
            <button type="button" wire:click="queue('{{ $entry->getKey() }}')" wire:loading.attr="disabled">Join competition</button>
            <button type="button" wire:click="refreshLeaderboard('{{ $entry->getKey() }}')" wire:loading.attr="disabled">View leaderboard</button>
        </article>
    @empty
        <p role="status">No competitions are available.</p>
    @endforelse
    @if($leaderboard !== [])<h3>Leaderboard</h3><ol>@foreach($leaderboard as $rank)<li>{{ $rank['actor_id'] }} — {{ $rank['points'] }} points ({{ $rank['wins'] }} wins)</li>@endforeach</ol>@endif
</section>
