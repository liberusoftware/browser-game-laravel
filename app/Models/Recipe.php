<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'result_item_id',
        'result_quantity',
        'min_level',
        'success_rate',
        'crafting_time_seconds',
    ];

    public function resultItem()
    {
        return $this->belongsTo(Item::class, 'result_item_id');
    }

    public function materials()
    {
        return $this->hasMany(RecipeMaterial::class);
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_recipes')
            ->withPivot('learned_at')
            ->withTimestamps();
    }
}
