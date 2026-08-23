<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'total_quests_completed',
        'total_items_collected',
        'total_playtime_minutes',
        'highest_level_achieved',
        'total_experience_earned',
        'quests_in_progress',
        'achievements_unlocked',
    ];

    protected $casts = [
        'total_quests_completed' => 'integer',
        'total_items_collected' => 'integer',
        'total_playtime_minutes' => 'integer',
        'highest_level_achieved' => 'integer',
        'total_experience_earned' => 'integer',
        'quests_in_progress' => 'integer',
        'achievements_unlocked' => 'integer',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
