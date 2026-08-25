<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guild_Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'guild_id',
        'role',
        'joined_at',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }
}
