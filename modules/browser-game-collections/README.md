# Browser Game Collections

This package provides the core domain implementation for the Browser Game application. It owns the provider-neutral domain contracts, behavior, persistence, and authorization boundary for this capability.

## Installation

```bash
composer require liberusoftware/module-browser-game-collections
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. It defines collectible sets and entries, tracks actor progress transactionally, caps progress at each requirement, supports repeatable completion cycles, deduplicates retryable operations, enforces supplied tenant/team scope at the domain boundary, and emits post-commit progress/reward events for delivery integrations.

## License

MIT. See [LICENSE](LICENSE.md).
