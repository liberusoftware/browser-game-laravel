<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LiveOpsRollback extends Model
{
    protected $table = 'browser_game_live_ops_rollbacks';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['from_version' => 'integer', 'to_version' => 'integer', 'snapshot' => 'array'];
    }

    public function liveOps(): BelongsTo
    {
        return $this->belongsTo(LiveOpsRecord::class, 'live_ops_id');
    }
}
