<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Models;

use Illuminate\Database\Eloquent\Model;

final class CraftingProfession extends Model
{
    protected $table = 'browser_game_crafting_professions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['level' => 'integer', 'experience' => 'integer', 'data' => 'array'];
    }
}
