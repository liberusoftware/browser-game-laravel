# Browser Game Accounts Api

This package exposes the versioned /api/v1/browser-game/accounts contract for identity updates, age/region policy, privacy, sessions, recovery, and deletion requests. It delegates all domain behavior to the matching core module and enforces tenant visibility before mutations.

## Installation

```bash
composer require liberusoftware/module-browser-game-accounts-api
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. The api adapter does not own domain state and must be used with the matching core package.

## License

MIT. See [LICENSE](LICENSE.md).
