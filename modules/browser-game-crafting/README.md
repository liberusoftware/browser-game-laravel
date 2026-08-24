# Browser Game Crafting

This package provides the core domain implementation for the Browser Game application. It owns the provider-neutral domain contracts, behavior, persistence, and authorization boundary for this capability.

## Installation

```bash
composer require liberusoftware/module-browser-game-crafting
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. 

The public crafting boundary owns recipe material validation, profession progression, resource locking and consumption, quality and success rolls, idempotent queues, discovery, cancellation refunds, salvage, and provenance-bearing outputs. It does not depend on application models or presentation frameworks.

## License

MIT. See [LICENSE](LICENSE.md).
