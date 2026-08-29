<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreLivewire\Livewire;

use Illuminate\Validation\ValidationException;
use JsonException;
use Liberu\BrowserGame\GameCore\Models\GameWorld;
use Liberu\BrowserGame\GameCore\Queries\GameCoreOverview as OverviewQuery;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;
use Livewire\Component;

final class WorldOverview extends Component
{
    public string $worldId;

    public ?string $message = null;

    public string $currentAt = '';

    public string $clockSpeed = '1';

    public bool $clockPaused = false;

    public int $rulesetVersion = 1;

    public string $rulesJson = '{}';

    public int $contentVersion = 1;

    public string $contentHash = '';

    public string $manifestJson = '{}';

    public string $featureKey = '';

    public bool $featureEnabled = false;

    public int $featureRolloutPercentage = 100;

    public string $featureConstraintsJson = '{}';

    public string $maintenanceStatus = 'active';

    public string $maintenanceMessage = '';

    public function mount(string $worldId): void
    {
        $this->worldId = $worldId;
        $this->currentAt = now()->format('Y-m-d\\TH:i');
    }

    public function updateClock(): void
    {
        $this->validate([
            'currentAt' => ['required', 'date'],
            'clockSpeed' => ['required', 'numeric', 'min:0'],
            'clockPaused' => ['boolean'],
        ]);
        $this->setClock($this->currentAt, $this->clockSpeed, $this->clockPaused);
    }

    public function setClock(string $currentAt, string $speed = '1', bool $paused = false): void
    {
        $context = $this->context();
        app(GameCoreManager::class)->setClock($context, $this->world(), $currentAt, $speed, $paused);
        $this->message = 'Game clock updated.';
    }

    public function publishRuleset(int $version, array $rules = []): void
    {
        $context = $this->context();
        app(GameCoreManager::class)->publishRuleset($context, $this->world(), $version, $rules);
        $this->message = 'Ruleset published.';
    }

    public function publishRulesetFromForm(): void
    {
        $this->validate(['rulesetVersion' => ['required', 'integer', 'min:1'], 'rulesJson' => ['required', 'json']]);
        $this->publishRuleset($this->rulesetVersion, $this->decodePayload($this->rulesJson, 'rulesJson'));
    }

    public function publishContent(int $version, string $contentHash, array $manifest = []): void
    {
        $context = $this->context();
        app(GameCoreManager::class)->publishContentVersion($context, $this->world(), $version, $contentHash, $manifest);
        $this->message = 'Content version published.';
    }

    public function publishContentFromForm(): void
    {
        $this->validate([
            'contentVersion' => ['required', 'integer', 'min:1'],
            'contentHash' => ['required', 'string', 'max:128'],
            'manifestJson' => ['required', 'json'],
        ]);
        $this->publishContent($this->contentVersion, $this->contentHash, $this->decodePayload($this->manifestJson, 'manifestJson'));
    }

    public function setFeatureFlag(string $key, bool $enabled, int $rolloutPercentage = 100, array $constraints = []): void
    {
        app(GameCoreManager::class)->setFeatureFlag($this->context(), $this->world(), $key, $enabled, $rolloutPercentage, $constraints);
        $this->message = 'Feature flag updated.';
    }

    public function updateFeatureFlagFromForm(): void
    {
        $this->validate([
            'featureKey' => ['required', 'string', 'max:120'],
            'featureEnabled' => ['boolean'],
            'featureRolloutPercentage' => ['required', 'integer', 'min:0', 'max:100'],
            'featureConstraintsJson' => ['required', 'json'],
        ]);
        $this->setFeatureFlag($this->featureKey, $this->featureEnabled, $this->featureRolloutPercentage, $this->decodePayload($this->featureConstraintsJson, 'featureConstraintsJson'));
    }

    public function featureEnabled(string $key, array $attributes = []): bool
    {
        return app(OverviewQuery::class)->isEnabled($this->context(), $this->world(), $key, $attributes);
    }

    public function setMaintenance(string $status, ?string $message = null): void
    {
        app(GameCoreManager::class)->setMaintenance($this->context(), $this->world(), $status, $message);
        $this->message = 'Maintenance state updated.';
    }

    public function updateMaintenanceFromForm(): void
    {
        $this->validate([
            'maintenanceStatus' => ['required', 'in:scheduled,active,resolved'],
            'maintenanceMessage' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->setMaintenance($this->maintenanceStatus, $this->maintenanceMessage !== '' ? $this->maintenanceMessage : null);
    }

    public function render(): mixed
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $overview = app(OverviewQuery::class)->forWorld(new ArrayGameCoreContext(
            actor: $user?->getAuthIdentifier() === null ? null : (string) $user->getAuthIdentifier(),
            tenant: $team?->getAttribute('tenant_id'),
            team: $team?->getKey() === null ? null : (string) $team->getKey(),
        ), $this->worldId);

        return resolve('view')->make('browser-game-game-core-livewire::world-overview', ['overview' => $overview]);
    }

    private function context(): ArrayGameCoreContext
    {
        abort_unless(auth()->check(), 403);
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return new ArrayGameCoreContext(actor: (string) auth()->id(), tenant: $team?->getAttribute('tenant_id'), team: $team?->getKey() === null ? null : (string) $team->getKey());
    }

    private function world(): GameWorld
    {
        $context = $this->context();

        return GameWorld::query()->whereKey($this->worldId)
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $context->tenantId()))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $context->teamId()))
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function decodePayload(string $payload, string $field): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([$field => 'The value must contain valid JSON.']);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([$field => 'The JSON value must be an object.']);
        }

        return $decoded;
    }
}
