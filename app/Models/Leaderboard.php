<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'player_id',
        'value',
        'rank',
        'snapshot_at',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
