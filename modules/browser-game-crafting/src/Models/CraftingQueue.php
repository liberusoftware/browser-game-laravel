<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CraftingQueue extends Model
{
    use HasUuids;

    protected $table = 'browser_game_crafting_queues';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quality' => 'integer',
            'started_at' => 'datetime',
            'completes_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(CraftingRecord::class, 'recipe_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(CraftingOutput::class, 'queue_id');
    }
}
