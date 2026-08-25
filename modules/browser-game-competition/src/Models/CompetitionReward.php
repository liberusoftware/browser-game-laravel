<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompetitionReward extends Model
{
    protected $table = 'browser_game_competition_rewards';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'claimed_at' => 'datetime', 'data' => 'array'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(CompetitionRecord::class, 'competition_id');
    }
}
