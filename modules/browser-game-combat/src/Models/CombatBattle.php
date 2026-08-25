<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CombatBattle extends Model
{
    use HasUuids;

    protected $table = 'browser_game_combats';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['state' => 'array'];
    }
}
