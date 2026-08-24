# Browser Game Social Api

This package provides the api implementation for the Browser Game application. It exposes the documented HTTP contract and delegates domain behavior to the matching core module.

## Installation

```bash
composer require liberusoftware/module-browser-game-social-api
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. The api adapter does not own domain state and must be used with the matching core package.

## License

MIT. See [LICENSE](LICENSE.md).

