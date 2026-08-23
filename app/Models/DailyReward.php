<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'reward_date',
        'day_streak',
        'gold_rewarded',
        'experience_rewarded',
        'items_rewarded',
    ];

    protected $casts = [
        'reward_date' => 'date',
        'items_rewarded' => 'array',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
