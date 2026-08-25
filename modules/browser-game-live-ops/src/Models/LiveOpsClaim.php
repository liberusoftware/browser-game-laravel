<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LiveOpsClaim extends Model
{
    protected $table = 'browser_game_live_ops_claims';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['grant' => 'array'];
    }

    public function liveOps(): BelongsTo
    {
        return $this->belongsTo(LiveOpsRecord::class, 'live_ops_id');
    }
}
