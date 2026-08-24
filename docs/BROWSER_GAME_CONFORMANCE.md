# Browser Game conformance record

This record applies the Wayfinder requirements from issue #415 to the browser-game module set. The governing feature inventory is the Browser Game documentation matrix; the issue-specific contracts remain the source of truth for each domain, API, Filament 5, and Livewire 4 package.

## Module ownership

Each capability is independently namespaced under `Liberu\BrowserGame`, owns its migrations and domain actions, and exposes presentation adapters only through its matching `-api`, `-filament`, and `-livewire` packages. No package in this set depends on an application `App\` class or on another capability's private model/table.

| Capability | Domain boundary | Required surfaces | Current implementation boundary |
| --- | --- | --- | --- |
| Game Core | worlds, clocks, rulesets, content, flags, maintenance | API, Filament, Livewire | typed aggregates, scoped context, lifecycle actions |
| Accounts | identity, sessions, recovery, age/region, bans, privacy | API, Filament, Livewire | hashed session/recovery state and lifecycle events |
| Characters | creation, progression, statistics, skills, health, respec | API, Filament, Livewire | level-up and stat allocation actions |
| World | regions, locations, maps, travel, encounters, NPCs, resources, weather, unlocks | API, Filament, Livewire | typed catalog and idempotent travel |
| Combat | turns, abilities, effects, cooldowns, enemies, bosses, loot, logs, simulation | API, Filament, Livewire | deterministic battle/action boundary and catalog definitions |
| Quests | storylines, objectives, prerequisites, branches, dialogue, rewards, repeatability, progress | API, Filament, Livewire | objective progress and authored quest metadata |
| Items | definitions, inventory, equipment, durability, binding, containers, stacks, provenance | API, Filament, Livewire | transactional inventory mutations |
| Crafting | recipes, professions, resources, quality, queues, discovery, salvage, outputs | API, Filament, Livewire | transactional queue and provenance outputs |
| Economy | currencies, faucets/sinks, vendors, pricing, trading, auction fees, anti-abuse | API, Filament, Livewire | ledger, wallets, vendors, listings, fees, idempotency |
| Social | friends, parties, chat, mail, guilds, alliances, permissions, activity, reporting | API, Filament, Livewire | typed social records, memberships, activity, reports |
| Competition | PvP, matchmaking, seasons, rankings, leaderboards, rewards, anti-collusion | API, Filament, Livewire | queues, ratings, matches, results, evidence |
| Collections | achievements, titles, reputation, pets, mounts, housing, cosmetics, progress | API, Filament, Livewire | typed collection categories, entries, progress, completion |
| Live Ops | daily activities, events, seasons, schedules, announcements, grants, rollback | API, Filament, Livewire | publication, claims, grants, versioning, rollback evidence |
| Moderation and Analytics | reports, sanctions, appeals, telemetry, funnels, balance, economy, fraud, health | API, Filament, Livewire | typed evidence records and resolution transitions |

## Ordered verification gates

1. `php artisan module:validate` must pass after every capability change.
2. Every PHP file in the affected capability and its three adapters must pass `php -l` and Pint.
3. Each package boundary and adapter suite must pass; database behavior suites run from the package directory so Testbench boots the package context.
4. Each OpenAPI fragment must pass YAML lint and its route/controller operations must delegate to public domain actions.
5. Root authorization, release-scope, and full Pest suites must pass before a release commit.
6. Generated caches and runtime logs remain untracked; only source, migrations, tests, and documentation are committed.

## Compatibility and legacy carry-forward

Legacy marketplace transfers, inventory locking/equipment behavior, crafting queues and salvage, quest progress, combat actions, guild/social membership, leaderboard/ranking, and daily reward/grant concepts are represented by the corresponding capability boundary rather than retained as application-owned business logic. Cross-capability payment, notifications, identity, and authorization integrations remain explicit host contracts.

Issues #400–#415 remain open. This document records implementation conformance and does not alter issue state.
