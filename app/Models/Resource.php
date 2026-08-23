<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'resource_type',
        'quantity',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
