# Player experience and game setup

For a fresh installation, configure the database and run:

```sh
php artisan migrate
php artisan db:seed
npm run build
```

To add the starter adventure to an existing installation without running the account seeders:

```sh
php artisan migrate
php artisan db:seed --class=StarterAdventureSeeder
npm run build
```

Super administrators can also use **Admin → Overview → Game setup wizard → Install starter adventure** after migrating. The starter seeder is safe to repeat: stable keys prevent duplicate items and quests, and existing content edits and player progress are preserved. It creates no demonstration login accounts and does not attach anything to existing players automatically.

## A ready-to-play starter adventure

**Dawnvale** contains 39 story quests: the original five-chapter introduction plus twelve optional branches. All eight classes can complete every branch.

The introduction remains unchanged:

1. A New Arrival in Dawnvale — 25 XP and a Silverleaf Sprig.
2. The Silverleaf Trail — 50 XP and a Silverleaf Sprig.
3. A Safer Forest Crossing — 75 XP and Wayfinder Trail Boots.
4. Light at the Old Watchtower — 100 XP and an Old Watch Lantern Charm.
5. Guardian of Dawnvale — 150 XP and a Dawnvale Guardian Medal.

The expansion adds:

| Branch | Quests | Unlocks after | Rewards |
| --- | --- | --- | --- |
| Woodland | The Willow Ferry → Whispers of the Hidden Grove → The Briarwarden’s Oath | A Safer Forest Crossing | Ferryman Gloves, Moonseed Crystal, Briarwarden Mantle |
| Chapel | Bells Above the Valley → The Echo Beneath the Chapel | Guardian of Dawnvale | Bellkeeper Hood, Pendant of Quiet Echoes |
| Caravan | The Lost Amber Caravan → Across the Singing Sands → The Clockwork Constellation | Guardian of Dawnvale | Amber Road Compass, Singing Glass Sand, Starforged Survey Staff |
| Coast | Stormwatch on the Coast → The Tidebound Archive | Light at the Old Watchtower | Stormwatch Boots, Tidekeeper Ring |

Two further content waves add 24 quests and 24 items:

| Branch | Quests | Unlocks after | Final reward |
| --- | --- | --- | --- |
| Forge | A Delivery to Emberforge → The Tempered Promise → The Hearthguard Trial | Guardian of Dawnvale | Hearthguard Blade |
| Mountain | Markers on Frostpass → The Snowglass Seam → The Summit Signal | A Safer Forest Crossing | Summit Sentinel Bow |
| Marsh | The Reedwater Post → Lights on the Fen → The Marshkeeper’s Sluice | The Willow Ferry | Marshkeeper Signet |
| Sky station | The Windward Mail Run → Aligning the Cloudlens → The Sky Chart Restored | The Lost Amber Caravan | Skyward Survey Hammer |
| Harvest | The Silent Millrace → The Granary Ledger → A Harvest for Every Hearth | Guardian of Dawnvale | Harvestwarden Vest |
| Mine | The Copper Burrow → A Crystal’s Resonance → The Deep Road Delivery | The Tempered Promise | Deep Road Escort Axe |
| Orchard | Mist in the Moon Orchard → The Moonwater Basin → The Orchard Covenant | The Briarwarden’s Oath | Moonbough Keeper Bow |
| Harbor | The Sunken Register → The Harbor Cipher → Homeward Sails | The Tidebound Archive | Homeward Captain’s Sabre |

Every new quest has a distinct inventory reward. Players can pursue unlocked branches in any order.

Players accept each chapter in the quest journal, then choose a response to its scene. Wrong choices show a hint and can be retried. Each successful chapter unlocks its dependent quests; completion grants its rewards once. With default starting XP and level, the original introduction awards 400 XP and reaches level 5. Completing all 39 quests awards 7,480 XP and reaches level 75. Ordinary administrator-created quests do not receive demonstration completion buttons.

Account setup offers eight classes:

| Class | Health | Mana | Strength | Defense | Agility | Intelligence | Starter weapon |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| Warrior | 120 | 30 | 15 | 14 | 8 | 6 | Training Sword |
| Ranger | 100 | 45 | 10 | 10 | 16 | 8 | Scouting Bow |
| Mage | 85 | 100 | 6 | 8 | 10 | 17 | Apprentice Staff |
| Rogue | 90 | 40 | 11 | 7 | 18 | 8 | Practice Dagger |
| Paladin | 125 | 50 | 12 | 17 | 6 | 9 | Oathkeeper Hammer |
| Cleric | 105 | 85 | 8 | 13 | 7 | 15 | Acolyte Mace |
| Druid | 100 | 80 | 9 | 10 | 11 | 14 | Living Branch |
| Bard | 95 | 70 | 7 | 9 | 14 | 14 | Wayfarer Lute |

Each receives its class weapon, a Travel Cloak, and three Silverleaf Sprigs. Starter items are placed in inventory; they are not automatically equipped. The 97 example items cover weapons, armor, materials, and accessories, with rarity and equipment bonuses.

Revisiting setup preserves the player's class, stats, progress, and inventory and cannot repeatedly grant a kit. Existing characters retain their stats when selecting a class. If setup happened before the starter content was installed, revisit account setup afterward to claim the kit. When registration is closed, new characters are not created.

Rerun `php artisan db:seed --class=StarterAdventureSeeder` (or use the admin install button) to add the expanded catalog to an original installation. Existing classes, item quantities, edited descriptions, and completed quests remain intact. Quest and item expansions need only the original starter-adventure migration. The battle arena has its own additional migration; run `php artisan migrate` before installing NPCs.

Definitions live in `config/starter-adventure.php`; class stats apply to newly created characters. Quest text and item definitions are inserted once and remain editable through the existing admin resources.

## Panels

- `/app`: adventure overview, XP progression, class, active quests, inventory, and community links.
- `/app/account-setup`: profile, team, character class, and optional API token.
- `/app/adventure-guide`: class previews and a short beginner guide. Completion is saved per account.
- `/app/battle-arena`: turn-based NPC encounters, opt-in player duels, equipment loadouts, and private battle reports. See [Arena guide](arena.md).
- `/app/quest-journal`: paginated quests, rewards, status, and interactive starter chapters. Acceptance is authenticated, idempotent, and respects the active quest limit.
- Admin → Overview → Game setup wizard: existing global game settings and starter content installation. Only super administrators may open or save it. Saving does not reset players.

The latest expansion adds exactly 50 items (ten regional sets of a weapon, armor, headwear/boots/gloves, an accessory, and a material) and 50 NPCs. Each new item is assigned to a distinct new opponent as its guaranteed eligible victory drop; equipment requirements and encounter levels increase together.

NPC seeding is included in normal `db:seed` and the admin installer. Existing installations can run `php artisan db:seed --class=NpcSeeder`; it also installs missing starter content. Enable PvP explicitly in the admin wizard to allow player challenges.

The shared Filament stylesheet supports mobile layouts, light/dark appearance, keyboard focus, and reduced motion.
