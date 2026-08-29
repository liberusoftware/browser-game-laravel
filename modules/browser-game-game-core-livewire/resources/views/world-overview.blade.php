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
    <form wire:submit="updateClock">
        <h3>Update game clock</h3>
        <label>Current time <input type="datetime-local" wire:model="currentAt"></label>
        <label>Speed <input type="number" min="0" step="0.01" wire:model="clockSpeed"></label>
        <label><input type="checkbox" wire:model="clockPaused"> Paused</label>
        <button type="submit" wire:loading.attr="disabled">Save clock</button>
    </form>
    <form wire:submit="publishRulesetFromForm">
        <h3>Publish ruleset</h3>
        <label>Version <input type="number" min="1" wire:model="rulesetVersion"></label>
        <label>Rules <textarea wire:model="rulesJson"></textarea></label>
        <button type="submit" wire:loading.attr="disabled">Publish ruleset</button>
    </form>
    <form wire:submit="publishContentFromForm">
        <h3>Publish content</h3>
        <label>Version <input type="number" min="1" wire:model="contentVersion"></label>
        <label>Content hash <input type="text" wire:model="contentHash"></label>
        <label>Manifest <textarea wire:model="manifestJson"></textarea></label>
        <button type="submit" wire:loading.attr="disabled">Publish content</button>
    </form>
    <form wire:submit="updateFeatureFlagFromForm">
        <h3>Update feature flag</h3>
        <label>Key <input type="text" wire:model="featureKey"></label>
        <label><input type="checkbox" wire:model="featureEnabled"> Enabled</label>
        <label>Rollout <input type="number" min="0" max="100" wire:model="featureRolloutPercentage"></label>
        <label>Constraints <textarea wire:model="featureConstraintsJson"></textarea></label>
        <button type="submit" wire:loading.attr="disabled">Save feature flag</button>
    </form>
    <form wire:submit="updateMaintenanceFromForm">
        <h3>Maintenance</h3>
        <label>Status <select wire:model="maintenanceStatus"><option value="scheduled">Scheduled</option><option value="active">Active</option><option value="resolved">Resolved</option></select></label>
        <label>Message <textarea wire:model="maintenanceMessage"></textarea></label>
        <button type="submit" wire:loading.attr="disabled">Save maintenance</button>
    </form>
</section>
