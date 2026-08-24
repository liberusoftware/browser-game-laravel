<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class GameMaintenanceState extends Model
{
    use HasUuids;

    protected $table = 'browser_game_maintenance_states';

    protected $fillable = ['world_id', 'status', 'message', 'starts_at', 'ends_at', 'changed_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }
}
