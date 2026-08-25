# Browser Game World

The World module owns the typed world catalog and travel provenance. `WorldManager` validates catalog kinds, scope, unlock requirements, and travel targets; `WorldQuery` exposes authorized read models. Catalog payloads are data-only JSON and never executable input.

Supported kinds are `region`, `location`, `map`, `encounter`, `npc`, `resource`, `weather`, and `unlock`. Travel writes an immutable record with actor, origin, destination, and idempotency key. Actor unlocks are persisted in the module-owned unlock table; travel never trusts a client-supplied `unlocked` flag and requires a granted unlock for destinations with an `unlock_key`.
