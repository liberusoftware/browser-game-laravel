<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class LiveOpsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_live_ops';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
