<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CombatDefinition extends Model
{
    use HasUuids;

    protected $table = 'browser_game_combat_catalog';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['cooldown' => 'integer', 'effects' => 'array', 'data' => 'array'];
    }
}
