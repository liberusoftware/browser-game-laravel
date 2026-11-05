# Dawnvale battle arena

After configuring the database:

```sh
php artisan migrate
php artisan db:seed --class=NpcSeeder
npm run build
```

`NpcSeeder` also installs missing starter quests and items. Normal database seeding and the admin wizard include NPCs. All seeders preserve edited content and existing progression.

Open `/app/battle-arena` or use **Enter the battle arena** on the adventure overview.

## Tactical NPC encounters

Sixty opponents span levels 1–84, across training, normal, elite and boss encounters. The hunting board shows twelve encounters per page.

The original ten opponents remain available:

| NPC | Level | Behavior | Region |
| --- | ---: | --- | --- |
| Practice Sentinel | 1 | Balanced | Dawnvale |
| Goblin Forager | 2 | Aggressive | Silverleaf Trail |
| Briar Stalker | 4 | Defensive | Hidden Grove |
| Crypt Acolyte | 6 | Mystic | Echo Crypt |
| Ember Forgemaster | 9 | Aggressive | Emberforge |
| Frostpass Warden | 12 | Defensive | Frostpass |
| Fenlight Oracle | 16 | Mystic | Reedwater |
| Clockwork Colossus | 22 | Balanced | Clockwork Observatory |
| Tidebound Leviathan | 30 | Aggressive | Tide Archive |
| Sky-Crowned Drake | 45 | Mystic | Sky Station |

The expanded roster adds five opponents in each region, with a distinct item drop for every opponent:

| Region | NPC levels | Five example items |
| --- | --- | --- |
| Dawnvale Meadows | 1–5 | Meadowkeeper Crook, Vest, Boots, Badge, Golden Grain Bundle |
| Silverleaf Verge | 3–7 | Verge Bow, Scout Wrap, Scout Hood, Trail Compass, Dewleaf Fiber |
| Emberforge Depths | 8–12 | Cindersmith Sword, Coat, Gloves, Cinderheart Pendant, Cinder Ore |
| Upper Frostpass | 14–18 | Glacier Trail Axe, Coat, Boots, Ward Medallion, Ice Quartz |
| Reedwater Basin | 20–24 | Channel Staff, Robe, Cowl, Signet Ring, Luminous Reed Resin |
| Amber Road Outposts | 28–32 | Road Dagger, Cuirass, Escort Gloves, Escort Token, Raw Amber Shard |
| Moon Orchard Wilds | 38–42 | Scholar Staff, Scholar Mantle, Wanderer Boots, Heart Charm, Ancient Moonbark |
| Stormwatch Reach | 50–54 | Tempestwatch Sabre, Brigandine, Helm, Seal, Stormglass Salt Crystal |
| Starfall Pinnacle | 65–69 | Focus Rod, Observer Robe, Artificer Gloves, Orbit Ring, Unrefined Star Metal |
| Eclipse Bastion | 80–84 | Bastion Greatblade, Plate, Greaves, Warden Crown, Eclipsed Obsidian Ore |

The fifty new items bring the catalog to 97. Each has its own description, rarity, minimum level, and buy/sell prices; equipment also has slot-specific stat bonuses. Materials are collectible crafting examples and do not imply additional recipes or consumable effects.

Run `php artisan db:seed --class=NpcSeeder` again to install this expansion. Existing NPC edits, item edits, player inventory, quests, and battle records are preserved.

Encounters unlock three levels below the NPC’s level. The player chooses an action, then the NPC responds if still standing. Aggressive enemies favor power strikes, mystics prefer arcane attacks and mana recovery, defensive enemies guard periodically, and all enemies can mend at low health when they have mana.

Actions:

- Quick strike: physical damage without spending mana.
- Power strike: greater physical damage for 8 mana.
- Arcane burst: intelligence-based damage with reduced armor mitigation, for 12 mana.
- Guard & focus: halve the next incoming hit and recover 8 mana.
- Second wind: restore health for 15 mana.

Agility increases critical hit chance, capped at 25%. Equipped item bonuses and a level-based stat bonus are snapshotted at battle start. Loadouts cannot change during an encounter. Turns and action logs persist across page reloads.

NPC damage persists as a proportion of world health/mana when the battle ends; defeated players retain at least one health. The free shrine restores both resources between battles. NPC victories award cumulative XP, gold resources, and their listed loot; game XP/gold multipliers apply. Rewards for the same NPC are limited to once per ten minutes. Battles started inside that window remain playable as practice. A full inventory prevents a new distinct item drop, but existing stacks can grow. XP does not reset on level-up, and combat respects the maximum player level.

## Consensual player duels

The super administrator must enable PvP in the setup wizard. Both players must opt in and be within five levels of each other. A challenge requires explicit acceptance by its recipient. Pending challenges can be declined or withdrawn, and expire after fifteen minutes. A player may challenge the same recipient at most once per minute.

Duels start both combatants at full battle health/mana without restoring or damaging their world resources. The higher-agility player acts first; turns then alternate between the two actual players. NPC responses are not used for PvP.

Completed rated duels adjust Elo-style ratings with K=24, starting from 1000. A rematch of the same pair within ten minutes is practice and does not alter ratings. PvP awards neither XP nor gold. Forfeits count as losses in rated duels. Draws use the usual rating expectation formula.

Both modes end after forty submitted turns if neither side falls, comparing remaining health as a percentage of maximum. Equal percentages are a draw. Ten minutes without an action forfeits the battle for the player whose turn expired, including rating changes in a rated duel. Unaccepted challenges expire without penalties. A ten-second breather follows completed encounters.

Only one pending or active arena battle is allowed per player. Participant rows are locked in a consistent order; stale turn numbers cannot submit the same move twice. Battle reports are accessible only to their participants. The panel refreshes every five seconds to surface incoming challenges and opponent actions.

Definitions are in `config/arena.php`. Super administrators can create and edit encounters under **Admin → Game Content → NPC encounters**, including stats, behavior, rewards, and availability. Deactivate opponents rather than deleting them to preserve history. Changes to NPC definitions preserve existing seeded edits; delete the specific example record before reseeding if an intentional replacement is needed.
