# Browser Game Game Core

`liberusoftware/module-browser-game-game-core` owns the provider-neutral game control plane: worlds and shards, authoritative clocks, versioned rulesets and content, feature flags, and maintenance state.

The package is intentionally independent of HTTP, Filament, Livewire, themes, and application `App\\` classes. Presentation adapters consume its actions, queries, policies, and events. Every mutation is scoped by the caller supplied context, transactional, and safe to retry when an idempotency key is supplied. Feature evaluation is deterministic per actor, honors world overrides and constraints, and fails closed for an unavailable world or anonymous context.

## Installation

```bash
composer require liberusoftware/module-browser-game-game-core
php artisan migrate
```

The module is disabled by default. Enable it through the host module manifest after reviewing its permissions and retention policy. Disabling the module does not delete its data.

## Public boundary

- `GameCoreManager` provides typed lifecycle operations.
- `GameCoreOverview` provides a read model for an authorized world.
- `GameWorldCreated`, `GameClockChanged`, `GameRulesetPublished`, `GameContentVersionPublished`, `GameFeatureFlagChanged`, and `GameMaintenanceStateChanged` are integration events.
- `GameCorePolicy` is the default authorization boundary and must be composed with the host's tenant/team policy.

World identifiers are UUIDs. Rules and content payloads are versioned JSON and are never treated as executable input. Maintenance transitions are explicit and auditable by the host.
