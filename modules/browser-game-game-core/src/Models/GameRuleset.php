<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class GameRuleset extends Model
{
    use HasUuids;

    protected $table = 'browser_game_rulesets';

    protected $fillable = ['world_id', 'version', 'status', 'rules', 'published_at', 'published_by'];

    protected function casts(): array
    {
        return ['rules' => 'array', 'published_at' => 'immutable_datetime'];
    }
}
