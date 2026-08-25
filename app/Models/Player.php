<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read Pivot $pivot
 */
class Player extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'level',
        'experience',
        'rank',
        'score',
        'last_rank_update',
        'health',
        'max_health',
        'mana',
        'max_mana',
        'strength',
        'defense',
        'agility',
        'intelligence',
        'stat_points',
        'last_battle_at',
        'last_action_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_rank_update' => 'datetime',
        'last_battle_at' => 'datetime',
        'last_action_at' => 'datetime',
    ];

    /**
     * Calculate score based on player performance metrics.
     */
    public function calculateScore(): int
    {
        $levelScore = $this->level * 100;
        $experienceScore = $this->experience;

        return $levelScore + $experienceScore;
    }

    /**
     * Game notifications relationship.
     */
    public function gameNotifications(): HasMany
    {
        return $this->hasMany(GameNotification::class);
    }

    /**
     * Unread notifications relationship.
     */
    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(GameNotification::class)->where('is_read', false);
    }

    /**
     * Player profile relationship.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Player_Profile::class);
    }

    /**
     * Player's inventory items (pivot table records).
     */
    /** @return HasMany<Player_Item, $this> */
    public function playerItems(): HasMany
    {
        return $this->hasMany(Player_Item::class);
    }

    /**
     * Items in the player's inventory (many-to-many).
     */
    /** @return BelongsToMany<Item, $this> */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'player__items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Player's quest records (pivot table records).
     */
    public function playerQuests(): HasMany
    {
        return $this->hasMany(Player_Quest::class);
    }

    /**
     * Quests the player is involved with (many-to-many).
     */
    public function quests(): BelongsToMany
    {
        return $this->belongsToMany(Quest::class, 'player__quests')
            ->withPivot('status', 'progress_percentage', 'completed_at')
            ->withTimestamps();
    }

    /**
     * Active quests for the player.
     */
    public function activeQuests(): BelongsToMany
    {
        return $this->quests()->wherePivot('status', 'in-progress');
    }

    /**
     * Completed quests for the player.
     */
    public function completedQuests(): BelongsToMany
    {
        return $this->quests()->wherePivot('status', 'completed');
    }

    /**
     * Player's resources.
     */
    /** @return HasMany<\App\Models\Resource, $this> */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Guild memberships.
     */
    public function guildMemberships(): HasMany
    {
        return $this->hasMany(Guild_Membership::class);
    }

    /**
     * Guilds the player belongs to (many-to-many).
     */
    /** @return BelongsToMany<Guild, $this> */
    public function guilds(): BelongsToMany
    {
        return $this->belongsToMany(Guild::class, 'guild__memberships')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Player's primary guild.
     */
    public function guild(): HasOneThrough
    {
        return $this->hasOneThrough(
            Guild::class,
            Guild_Membership::class,
            'player_id',
            'id',
            'id',
            'guild_id'
        );
    }

    /**
     * Player statistics.
     */
    public function statistics(): HasOne
    {
        return $this->hasOne(PlayerStatistic::class);
    }

    /**
     * Player achievements (many-to-many).
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements')
            ->withPivot('unlocked_at', 'progress')
            ->withTimestamps();
    }

    /**
     * Player equipment.
     */
    /** @return HasMany<PlayerEquipment, $this> */
    public function equipment(): HasMany
    {
        return $this->hasMany(PlayerEquipment::class);
    }

    /**
     * Player skills (many-to-many).
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'player_skills')
            ->withPivot('level', 'last_used_at')
            ->withTimestamps();
    }

    /**
     * Player skill records.
     */
    public function playerSkills(): HasMany
    {
        return $this->hasMany(PlayerSkill::class);
    }

    /**
     * Battles as attacker.
     */
    public function battlesAsAttacker(): HasMany
    {
        return $this->hasMany(Battle::class, 'attacker_id');
    }

    /**
     * Battles as defender.
     */
    public function battlesAsDefender(): HasMany
    {
        return $this->hasMany(Battle::class, 'defender_id');
    }

    /**
     * All battles (attacker or defender).
     */
    public function battles()
    {
        return Battle::where('attacker_id', $this->id)
            ->orWhere('defender_id', $this->id);
    }

    /**
     * Recipes the player has learned.
     */
    /** @return BelongsToMany<Recipe, $this> */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'player_recipes')
            ->withPivot('learned_at')
            ->withTimestamps();
    }

    /**
     * Marketplace listings as seller.
     */
    public function sellerListings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class, 'seller_id');
    }

    /**
     * Marketplace purchases.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class, 'buyer_id');
    }

    /**
     * Leaderboard entries.
     */
    public function leaderboardEntries(): HasMany
    {
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * Daily rewards.
     */
    /** @return HasMany<DailyReward, $this> */
    public function dailyRewards(): HasMany
    {
        return $this->hasMany(DailyReward::class);
    }

    /**
     * Get total stats (base + equipment bonuses).
     */
    public function getTotalStats(): array
    {
        $baseStats = [
            'strength' => $this->strength,
            'defense' => $this->defense,
            'agility' => $this->agility,
            'intelligence' => $this->intelligence,
            'health' => $this->max_health,
            'mana' => $this->max_mana,
        ];

        $equipmentBonuses = [
            'strength' => 0,
            'defense' => 0,
            'agility' => 0,
            'intelligence' => 0,
            'health' => 0,
            'mana' => 0,
        ];

        foreach ($this->equipment()->with('item')->get() as $equipment) {
            $item = $equipment->item;
            $equipmentBonuses['strength'] += $item->strength_bonus ?? 0;
            $equipmentBonuses['defense'] += $item->defense_bonus ?? 0;
            $equipmentBonuses['agility'] += $item->agility_bonus ?? 0;
            $equipmentBonuses['intelligence'] += $item->intelligence_bonus ?? 0;
            $equipmentBonuses['health'] += $item->health_bonus ?? 0;
            $equipmentBonuses['mana'] += $item->mana_bonus ?? 0;
        }

        return [
            'base' => $baseStats,
            'equipment' => $equipmentBonuses,
            'total' => [
                'strength' => $baseStats['strength'] + $equipmentBonuses['strength'],
                'defense' => $baseStats['defense'] + $equipmentBonuses['defense'],
                'agility' => $baseStats['agility'] + $equipmentBonuses['agility'],
                'intelligence' => $baseStats['intelligence'] + $equipmentBonuses['intelligence'],
                'health' => $baseStats['health'] + $equipmentBonuses['health'],
                'mana' => $baseStats['mana'] + $equipmentBonuses['mana'],
            ],
        ];
    }
}
