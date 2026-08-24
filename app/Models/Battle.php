<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** @return BelongsTo<Player, $this> */
    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'attacker_id');
    }

    /** @return BelongsTo<Player, $this> */
    public function defender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'defender_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }
}
