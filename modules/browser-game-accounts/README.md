# Browser Game Accounts

This package provides the core domain implementation for the Browser Game application. It owns provider-neutral identity and account lifecycle behavior, persistence, policy state, and authorization boundaries.

The public AccountsManager supports identity validation, tenant-scoped account lifecycle transitions, age/region policy, hashed sessions, single-use recovery tokens, bans, privacy consent, and deletion requests. Session and recovery secrets are never persisted in plaintext. The optional API, Filament, and Livewire packages adapt this boundary without owning account rules.

## Installation

```bash
composer require liberusoftware/module-browser-game-accounts
```

The package requires PHP 8.5 and Laravel 13. Enable it through the host application's module composition. 

## License

MIT. See [LICENSE](LICENSE.md).
