<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Models;

use Illuminate\Database\Eloquent\Model;

final class CraftingResource extends Model
{
    protected $table = 'browser_game_crafting_resources';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }
}
