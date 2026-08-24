<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Quest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'experience_reward',
        'item_reward_id',
    ];

    // Relationships
    public function itemReward(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_reward_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player__quests')
            ->withPivot('status')
            ->withTimestamps();
    }
}
