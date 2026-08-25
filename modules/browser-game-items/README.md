# Browser Game Items

This package owns the provider-neutral item catalogue and player inventory boundary for the Browser Game application. Item definitions include type, rarity, equipment slot, stat bonuses, level requirements, and buy/sell values. Inventory mutations are transactional, lock the player/item row, reject invalid quantities, enforce tenant/team visibility when a scope is supplied, and never depend on an application `Player` model.

The public `ItemsManager` provides `define`, `addToInventory`, `removeFromInventory`, and `inventory`. Inventory operations accept optional tenant/team scope arguments; callers handling a scoped catalogue should always pass the current scope. Presentation packages translate these operations for API and Livewire consumers; Filament manages catalogue records. Marketplace and crafting integrations should use these public operations rather than querying the inventory table directly.

## Installation

```bash
composer require liberusoftware/module-browser-game-items
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. 

## License

MIT. See [LICENSE](LICENSE.md).
