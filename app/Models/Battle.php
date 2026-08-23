<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Battle extends Model
{
    use HasFactory;

    protected $fillable = [
        'attacker_id',
        'defender_id',
        'battle_type',
        'opponent_name',
        'opponent_level',
        'winner_id',
        'battle_log',
        'experience_gained',
        'gold_gained',
        'items_gained',
        'completed_at',
    ];

    protected $casts = [
        'battle_log' => 'array',
        'items_gained' => 'array',
        'completed_at' => 'datetime',
    ];

    public function attacker()
    {
        return $this->belongsTo(Player::class, 'attacker_id');
    }

    public function defender()
    {
        return $this->belongsTo(Player::class, 'defender_id');
    }

    public function winner()
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }
}
