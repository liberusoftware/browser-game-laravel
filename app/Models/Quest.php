<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'experience_reward',
        'item_reward_id',
    ];

    // Relationships
    public function itemReward()
    {
        return $this->belongsTo(Item::class, 'item_reward_id');
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player__quests')
            ->withPivot('status')
            ->withTimestamps();
    }
}
