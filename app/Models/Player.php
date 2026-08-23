<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
    public function gameNotifications()
    {
        return $this->hasMany(GameNotification::class);
    }

    /**
     * Unread notifications relationship.
     */
    public function unreadNotifications()
    {
        return $this->hasMany(GameNotification::class)->where('is_read', false);
    }

    /**
     * Player profile relationship.
     */
    public function profile()
    {
        return $this->hasOne(Player_Profile::class);
    }

    /**
     * Player's inventory items (pivot table records).
     */
    public function playerItems()
    {
        return $this->hasMany(Player_Item::class);
    }

    /**
     * Items in the player's inventory (many-to-many).
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'player__items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Player's quest records (pivot table records).
     */
    public function playerQuests()
    {
        return $this->hasMany(Player_Quest::class);
    }

    /**
     * Quests the player is involved with (many-to-many).
     */
    public function quests()
    {
        return $this->belongsToMany(Quest::class, 'player__quests')
            ->withPivot('status', 'progress_percentage', 'completed_at')
            ->withTimestamps();
    }

    /**
     * Active quests for the player.
     */
    public function activeQuests()
    {
        return $this->quests()->wherePivot('status', 'in-progress');
    }

    /**
     * Completed quests for the player.
     */
    public function completedQuests()
    {
        return $this->quests()->wherePivot('status', 'completed');
    }

    /**
     * Player's resources.
     */
    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Guild memberships.
     */
    public function guildMemberships()
    {
        return $this->hasMany(Guild_Membership::class);
    }

    /**
     * Guilds the player belongs to (many-to-many).
     */
    public function guilds()
    {
        return $this->belongsToMany(Guild::class, 'guild__memberships')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Player's primary guild.
     */
    public function guild()
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
    public function statistics()
    {
        return $this->hasOne(PlayerStatistic::class);
    }

    /**
     * Player achievements (many-to-many).
     */
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements')
            ->withPivot('unlocked_at', 'progress')
            ->withTimestamps();
    }

    /**
     * Player equipment.
     */
    public function equipment()
    {
        return $this->hasMany(PlayerEquipment::class);
    }

    /**
     * Player skills (many-to-many).
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'player_skills')
            ->withPivot('level', 'last_used_at')
            ->withTimestamps();
    }

    /**
     * Player skill records.
     */
    public function playerSkills()
    {
        return $this->hasMany(PlayerSkill::class);
    }

    /**
     * Battles as attacker.
     */
    public function battlesAsAttacker()
    {
        return $this->hasMany(Battle::class, 'attacker_id');
    }

    /**
     * Battles as defender.
     */
    public function battlesAsDefender()
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
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'player_recipes')
            ->withPivot('learned_at')
            ->withTimestamps();
    }

    /**
     * Marketplace listings as seller.
     */
    public function sellerListings()
    {
        return $this->hasMany(MarketplaceListing::class, 'seller_id');
    }

    /**
     * Marketplace purchases.
     */
    public function purchases()
    {
        return $this->hasMany(MarketplaceListing::class, 'buyer_id');
    }

    /**
     * Leaderboard entries.
     */
    public function leaderboardEntries()
    {
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * Daily rewards.
     */
    public function dailyRewards()
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
