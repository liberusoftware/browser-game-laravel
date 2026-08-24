<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CraftingRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_crafting';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
