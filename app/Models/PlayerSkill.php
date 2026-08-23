<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'skill_id',
        'level',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
