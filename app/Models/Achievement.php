<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'points',
        'requirement_type',
        'requirement_value',
    ];

    protected $casts = [
        'points' => 'integer',
        'requirement_value' => 'integer',
    ];

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_achievements')
            ->withPivot('unlocked_at', 'progress')
            ->withTimestamps();
    }
}
