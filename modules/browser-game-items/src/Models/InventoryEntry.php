<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryEntry extends Model
{
    protected $table = 'browser_game_inventory';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'durability' => 'integer',
            'max_durability' => 'integer',
            'is_bound' => 'boolean',
            'equipped_at' => 'datetime',
            'bound_at' => 'datetime',
            'provenance' => 'array',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemsRecord::class, 'item_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(self::class, 'container_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(self::class, 'container_id');
    }
}
