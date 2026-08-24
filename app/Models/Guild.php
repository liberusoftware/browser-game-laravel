<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Guild_Membership::class);
    }

    /** @return BelongsToMany<Player, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'guild__memberships')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /** @return BelongsToMany<Player, $this> */
    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'guild__memberships')
            ->wherePivot('role', 'leader')
            ->withPivot('joined_at')
            ->withTimestamps();
    }
}
