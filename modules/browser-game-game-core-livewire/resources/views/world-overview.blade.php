<section aria-labelledby="browser-game-world-heading">
    <h2 id="browser-game-world-heading">{{ $overview['world']->name }}</h2>
    @if($message)<p role="status">{{ $message }}</p>@endif
    <p>Status: {{ $overview['world']->status }}</p>
    @if ($overview['maintenance']?->status === 'active')
        <p role="status">{{ $overview['maintenance']->message ?: 'Maintenance is active.' }}</p>
    @endif
    <button type="button" wire:click="setMaintenance('active', 'Maintenance enabled')" wire:loading.attr="disabled">Enable maintenance</button>
    <button type="button" wire:click="setMaintenance('resolved')" wire:loading.attr="disabled">Resolve maintenance</button>
</section>
