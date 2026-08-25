<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CraftingRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_crafting';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'min_level' => 'integer',
            'success_rate' => 'decimal:2',
            'crafting_time_seconds' => 'integer',
            'materials' => 'array',
            'outputs' => 'array',
            'salvage' => 'array',
            'discovery_requirements' => 'array',
        ];
    }

    public function queues(): HasMany
    {
        return $this->hasMany(CraftingQueue::class, 'recipe_id');
    }
}
