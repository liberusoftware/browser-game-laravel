<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function resultItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'result_item_id');
    }

    /** @return HasMany<RecipeMaterial, $this> */
    public function materials(): HasMany
    {
        return $this->hasMany(RecipeMaterial::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_recipes')
            ->withPivot('learned_at')
            ->withTimestamps();
    }
}
