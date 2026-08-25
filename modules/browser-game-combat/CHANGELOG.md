# Changelog

## Unreleased

- Ignore client-supplied combat effects and persist only server-defined effects during action resolution.

## 1.0.0 - 2026-08-24

- Initial Browser Game Combat package release.
- Added atomic server-authoritative action resolution, cooldown enforcement, deterministic health state, completion/loot events, and bounded combat logs.
- Added typed PvE/PvP battle starters and ability, effect, enemy, boss, and loot definition actions.
- Rejected combat idempotency-key reuse for a different opponent or context.
