<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'mana_cost',
        'cooldown_seconds',
        'power',
        'min_level',
    ];

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_skills')
            ->withPivot('level', 'last_used_at')
            ->withTimestamps();
    }
}
