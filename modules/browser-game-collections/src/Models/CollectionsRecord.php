<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CollectionsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_collections';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array', 'repeatable' => 'boolean'];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CollectionEntry::class, 'collection_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CollectionProgress::class, 'collection_id');
    }
}
