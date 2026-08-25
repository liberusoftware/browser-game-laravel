<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CollectionEntry extends Model
{
    protected $table = 'browser_game_collection_entries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['required_quantity' => 'integer', 'reward' => 'array', 'data' => 'array'];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CollectionsRecord::class, 'collection_id');
    }
}
