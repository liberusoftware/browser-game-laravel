# Browser Game Combat Filament

This package provides the filament implementation for the Browser Game application. It provides the administrative Filament integration for the matching core module.

## Installation

```bash
composer require liberusoftware/module-browser-game-combat-filament
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. The Filament adapter does not own domain state and must be used with the matching core package. It registers battle and combat-definition resources covering abilities, effects, enemies, bosses, and loot.

## License

MIT. See [LICENSE](LICENSE.md).
