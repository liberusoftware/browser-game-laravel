<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CraftingDiscovery extends Model
{
    protected $table = 'browser_game_crafting_discoveries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['discovered_at' => 'datetime'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(CraftingRecord::class, 'recipe_id');
    }
}
