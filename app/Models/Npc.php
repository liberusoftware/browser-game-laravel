<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Npc extends Model
{
    protected $attributes = ['is_active' => true];

    protected $fillable = ['slug', 'name', 'description', 'region', 'difficulty', 'level', 'stats', 'behavior', 'experience_reward', 'gold_reward', 'loot_item_id', 'is_active'];

    protected $casts = ['stats' => 'array', 'is_active' => 'boolean', 'level' => 'integer'];

    public function lootItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'loot_item_id');
    }
}
