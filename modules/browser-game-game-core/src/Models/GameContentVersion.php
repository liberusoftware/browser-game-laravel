<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class GameContentVersion extends Model
{
    use HasUuids;

    protected $table = 'browser_game_content_versions';

    protected $fillable = ['world_id', 'version', 'status', 'content_hash', 'manifest', 'published_at', 'published_by'];

    protected function casts(): array
    {
        return ['manifest' => 'array', 'published_at' => 'immutable_datetime'];
    }
}
