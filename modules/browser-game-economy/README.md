# Browser Game Economy

This package provides the core domain implementation for the Browser Game application. It owns the provider-neutral domain contracts, behavior, persistence, and authorization boundary for this capability.

## Installation

```bash
composer require liberusoftware/module-browser-game-economy
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. 

The economy boundary owns currency definitions, atomic wallet ledgers, faucets and sinks, vendor offers, marketplace listings, fee calculation, idempotent transfers, settlement, cancellation, and transaction amount limits. Item movement remains an asset-reference contract so this package does not duplicate the Items module's private tables.

## License

MIT. See [LICENSE](LICENSE.md).
