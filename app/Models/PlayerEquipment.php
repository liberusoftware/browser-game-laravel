<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerEquipment extends Model
{
    use HasFactory;

    protected $table = 'player_equipment';

    protected $fillable = [
        'player_id',
        'slot',
        'item_id',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
