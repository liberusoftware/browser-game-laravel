<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player_Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'avatar_url',
        'bio',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
