<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class GameClock extends Model
{
    use HasUuids;

    protected $table = 'browser_game_clocks';

    protected $fillable = ['world_id', 'current_at', 'speed', 'paused', 'updated_by'];

    protected function casts(): array
    {
        return ['current_at' => 'immutable_datetime', 'speed' => 'decimal:6', 'paused' => 'boolean'];
    }
}
