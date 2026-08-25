# Browser Game Quests

This package provides the core domain implementation for the Browser Game application. It owns the provider-neutral domain contracts, behavior, persistence, and authorization boundary for this capability.

## Installation

```bash
composer require liberusoftware/module-browser-game-quests
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. 

Quest progress and idempotent lifecycle operations are stored in the quest's tenant/team context.

## License

MIT. See [LICENSE](LICENSE.md).
