# Game Architecture

## Overview

The browser game is a persistent-browser-based game (PBBG) with real-time interactions, persistent world state, and asynchronous gameplay loops. It supports player progression, quests, combat, crafting, inventory management, guilds, marketplace trading, and competitive leaderboards.

## Player System

### Profile

| Field | Description |
|-------|-------------|
| `username` | Unique display name |
| `level` | Current character level |
| `experience` | XP toward next level |
| `stats` | JSON map of core attributes (strength, agility, intelligence) |
| `currency` | Soft and hard currency balances |
| `status` | `active`, `inactive`, `banned` |

### Progression

Progression uses a configurable XP curve with level-gated content unlocks:

```php
LevelCurve::make()
    ->baseXp(100)
    ->multiplier(1.5)
    ->maxLevel(100);
```

### Achievements

Achievements are rule-based unlocks evaluated on player actions:

```php
Achievement::make('first_blood')
    ->condition('kills >= 1')
    ->reward(['xp' => 100, 'title' => 'Warrior']);
```

## Quest System

### Quest Types

| Type | Description |
|------|-------------|
| `kill` | Defeat N enemies of a type |
| `collect` | Gather N items |
| `explore` | Visit a location |
| `escort` | Protect an NPC to a destination |
| `chain` | Multi-step sequential objectives |

### Quest Structure

```php
Quest::make('goblin_threat')
    ->type('kill')
    ->objective('goblin', count: 10)
    ->reward(['xp' => 500, 'gold' => 200, 'item' => 'iron_sword'])
    ->timeLimit('2 hours');
```

### Quest Chains

Quests can be chained so completing one unlocks the next. Chain progress is persisted per player.

## Combat System

### Mechanics

Combat is turn-based with real-time cooldowns. The core formula:

```
damage = (attacker.power * skill_multiplier) - defender.defense
```

### Combat Types

- **PvE**: Player vs. environment (NPCs, monsters)
- **PvP**: Player vs. player (arena, open world, guild wars)

### Balancing

Balance parameters are data-driven and hot-reloadable:

```php
CombatConfig::set('pvp.damage_modifier', 0.75);
CombatConfig::set('pve.crit_chance', 0.15);
```

### Combat Log

Every combat encounter is logged with full action history for replay and dispute resolution.

## Crafting System

### Recipes

```php
Recipe::make('iron_sword')
    ->requires(['iron_ore' => 3, 'wood' => 2])
    ->skill('blacksmithing', level: 5)
    ->output('iron_sword', count: 1)
    ->craftTime('30 seconds');
```

### Skill Trees

Crafting skills are organized into trees with prerequisites:

```
Blacksmithing
├── Apprentice (1-25)
│   ├── Iron Weapons
│   └── Iron Armor
├── Journeyman (26-50)
│   ├── Steel Weapons
│   └── Steel Armor
└── Master (51-100)
    ├── Mithril Weapons
    └── Enchanting
```

### Material Sources

Materials are obtained through gathering (mining, logging, herbalism), combat drops, marketplace purchase, and quest rewards.

## Inventory System

### Storage

| Container | Capacity | Description |
|-----------|----------|-------------|
| `backpack` | 20 slots | Default player inventory |
| `warehouse` | 100 slots | Persistent storage |
| `equipped` | 8 slots | Active equipment |
| `guild_bank` | 200 slots | Shared guild storage |

### Item Properties

Items are defined in a data-driven item table with properties: `type`, `rarity`, `level_requirement`, `stats`, `durability`, `tradable`, `stackable`.

### Trading

Player-to-player trading uses a secure trade window with dual confirmation:

```php
Trade::between($playerA, $playerB)
    ->offer($playerA, ['item' => 'iron_sword', 'gold' => 100])
    ->offer($playerB, ['item' => 'health_potion', 'count' => 5])
    ->confirmBoth()
    ->execute();
```

## Guild System

### Creation and Membership

Guilds are created by players for a gold cost. Membership supports ranks with configurable permissions:

| Rank | Permissions |
|------|-------------|
| `leader` | All permissions, disband guild |
| `officer` | Invite, kick, manage bank, declare war |
| `member` | Chat, contribute, access bank |
| `recruit` | Chat only |

### Guild Wars

Guild wars are declared conflicts with configurable rules:

```php
GuildWar::declare($guildA, $guildB)
    ->duration('48 hours')
    ->objective('territory_control')
    ->reward(['gold' => 10000, 'territory' => 'north_pass']);
```

## Marketplace

### Listings

```php
Listing::create($seller)
    ->item('dragon_scale')
    ->price(5000, currency: 'gold')
    ->duration('7 days')
    ->type('fixed_price');
```

### Order Types

| Type | Description |
|------|-------------|
| `fixed_price` | Buy now at listed price |
| `auction` | Bidding with time limit |
| `escrow` | Third-party held items for high-value trades |

### Escrow

High-value transactions use an escrow service that holds items and payment until both parties confirm delivery.

## Leaderboards

### Categories

| Category | Metric | Period |
|----------|--------|--------|
| `level` | Player level | All-time |
| `combat` | PvP kills | Weekly |
| `crafting` | Crafting skill total | Monthly |
| `wealth` | Net worth | All-time |
| `guild` | Guild war wins | Seasonal |

### Rewards

Leaderboard rewards are distributed at period end with tiered prize pools:

| Rank | Reward |
|------|--------|
| 1 | 10,000 gold + exclusive title |
| 2-10 | 5,000 gold |
| 11-100 | 1,000 gold |
| 101-1000 | 100 gold |

## Notification and Event System

### Game Events

Game events are dispatched through the event bus and can trigger notifications, achievements, and quest progress:

```php
GameEvent::dispatch(new PlayerLeveledUp($player, $newLevel));
```

### Notification Channels

| Channel | Use Case |
|---------|----------|
| `in-game` | Real-time UI notifications |
| `email` | Important account events |
| `push` | Mobile push for time-sensitive events |

### Event Listeners

```php
Event::listen(PlayerLeveledUp::class, [
    GrantLevelRewards::class,
    NotifyPlayer::class,
    UpdateLeaderboard::class,
]);
```

## Module Boundaries and Extension Points

| Boundary | Extension Point | Mechanism |
|----------|----------------|-----------|
| Player | Custom stats, progression curves | `StatTypeInterface` binding + config |
| Quests | Custom quest types, objectives | `QuestTypeInterface` binding |
| Combat | Custom combat mechanics, formulas | `CombatEngineInterface` binding |
| Crafting | Custom recipes, skill trees | Data-driven recipe/skill definitions |
| Inventory | Custom containers, item types | `ContainerInterface` + item table |
| Guild | Custom ranks, permissions | Rank/permission configuration |
| Marketplace | Custom listing types, escrow rules | `ListingTypeInterface` binding |
| Leaderboards | Custom categories, periods | `LeaderboardCategoryInterface` binding |
| Events | Custom game events, listeners | Laravel event dispatcher |
| Notifications | Custom channels | `NotificationChannelInterface` binding |
