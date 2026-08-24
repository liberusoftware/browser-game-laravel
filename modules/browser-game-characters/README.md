# Browser Game Characters

`liberusoftware/module-browser-game-characters` owns character identity and progression. It stores characters in a module-owned table, applies all level/health/respec invariants in `CharactersManager`, and emits past-tense events after successful transactions. It has no dependency on HTTP, Filament, Livewire, themes, or application classes.

Experience is authoritative server-side: every award recalculates level and clamps health. Respec validates the requested point allocation, resets skills transactionally, and increments an auditable respec count.
