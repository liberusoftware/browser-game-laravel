<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ItemsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_items';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'strength_bonus' => 'integer',
            'defense_bonus' => 'integer',
            'agility_bonus' => 'integer',
            'intelligence_bonus' => 'integer',
            'health_bonus' => 'integer',
            'mana_bonus' => 'integer',
            'max_durability' => 'integer',
            'max_stack' => 'integer',
            'min_level' => 'integer',
            'sell_price' => 'integer',
            'buy_price' => 'integer',
        ];
    }

    public function inventoryEntries(): HasMany
    {
        return $this->hasMany(InventoryEntry::class, 'item_id');
    }
}
