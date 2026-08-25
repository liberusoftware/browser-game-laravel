<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player_Quest extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'quest_id',
        'status',
        'progress_percentage',
        'completed_at',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }
}
