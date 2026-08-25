<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CollectionProgress extends Model
{
    protected $table = 'browser_game_collection_progress';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'completion_count' => 'integer', 'completed_at' => 'datetime', 'reward_claimed_at' => 'datetime', 'data' => 'array'];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CollectionsRecord::class, 'collection_id');
    }
}
