<section aria-labelledby="browser-game-world-heading">
    <h2 id="browser-game-world-heading">{{ $overview['world']->name }}</h2>
    @if($message)<p role="status">{{ $message }}</p>@endif
    <p>Status: {{ $overview['world']->status }}</p>
    @if ($overview['clock'])
        <section aria-labelledby="browser-game-clock-heading">
            <h3 id="browser-game-clock-heading">Game clock</h3>
            <dl>
                <dt>Current time</dt>
                <dd>{{ $overview['clock']->current_at?->toISOString() ?: 'Not set' }}</dd>
                <dt>Speed</dt>
                <dd>{{ $overview['clock']->speed }}</dd>
                <dt>State</dt>
                <dd>{{ $overview['clock']->paused ? 'Paused' : 'Running' }}</dd>
            </dl>
        </section>
    @endif
    @if ($overview['ruleset'])
        <p>Ruleset version: {{ $overview['ruleset']->version }}</p>
    @endif
    @if ($overview['content_version'])
        <p>Content version: {{ $overview['content_version']->version }}</p>
    @endif
    @if ($overview['feature_flags']->isNotEmpty())
        <section aria-labelledby="browser-game-feature-flags-heading">
            <h3 id="browser-game-feature-flags-heading">Feature flags</h3>
            <ul>
                @foreach ($overview['feature_flags'] as $flag)
                    <li>
                        <code>{{ $flag->key }}</code>:
                        {{ $flag->enabled ? 'enabled' : 'disabled' }}
                        ({{ $flag->rollout_percentage }}%)
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
    @if ($overview['maintenance']?->status === 'active')
        <p role="status">{{ $overview['maintenance']->message ?: 'Maintenance is active.' }}</p>
    @endif
    <button type="button" wire:click="setMaintenance('active', 'Maintenance enabled')" wire:loading.attr="disabled">Enable maintenance</button>
    <button type="button" wire:click="setMaintenance('resolved')" wire:loading.attr="disabled">Resolve maintenance</button>
</section>
