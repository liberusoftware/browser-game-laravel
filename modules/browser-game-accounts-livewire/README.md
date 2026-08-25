# Browser Game Accounts Livewire

This package provides the server-driven Livewire integration for the matching core module, including validated identity updates and account deletion requests. Components resolve accounts through the public query boundary and do not own persistence or authorization rules.

## Installation

```bash
composer require liberusoftware/module-browser-game-accounts-livewire
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. The livewire adapter does not own domain state and must be used with the matching core package.

## License

MIT. See [LICENSE](LICENSE.md).
