# Changelog

## Unreleased

- Scope crafting player state, queues, discoveries, professions, resources, and idempotency by tenant and team context.

## 1.0.0 - 2026-08-24

- Initial Browser Game Crafting package release.
- Add recipes, professions, resources, quality, queues, discovery, salvage, and output records.
- Make discovery, queueing, completion, cancellation, and salvage transitions transactional and retry-safe.
- Add post-commit lifecycle events and locked profession progression.
