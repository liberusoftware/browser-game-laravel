<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'rarity',
        'slot',
        'strength_bonus',
        'defense_bonus',
        'agility_bonus',
        'intelligence_bonus',
        'health_bonus',
        'mana_bonus',
        'min_level',
        'sell_price',
        'buy_price',
    ];

    // Relationships
    /**
     * Get all player items that have this item.
     */
    public function playerItems()
    {
        return $this->hasMany(Player_Item::class);
    }

    /**
     * Get all players that have this item.
     */
    public function players()
    {
        return $this->belongsToMany(Player::class, 'player__items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function questRewards()
    {
        return $this->hasMany(Quest::class, 'item_reward_id');
    }
}
