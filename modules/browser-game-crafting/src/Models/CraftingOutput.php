<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CraftingOutput extends Model
{
    protected $table = 'browser_game_crafting_outputs';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'quality' => 'integer', 'provenance' => 'array'];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(CraftingQueue::class, 'queue_id');
    }
}
