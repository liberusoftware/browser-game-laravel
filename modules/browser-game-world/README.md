# Browser Game World

The World module owns the typed world catalog and travel provenance. `WorldManager` validates catalog kinds, scope, unlock requirements, and travel targets; `WorldQuery` exposes authorized read models. Catalog payloads are data-only JSON and never executable input.

Supported kinds are `region`, `location`, `map`, `encounter`, `npc`, `resource`, `weather`, and `unlock`. Travel writes an immutable record with actor, origin, destination, and idempotency key.
