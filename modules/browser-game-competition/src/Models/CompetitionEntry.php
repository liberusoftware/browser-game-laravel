<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompetitionEntry extends Model
{
    protected $table = 'browser_game_competition_entries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'wins' => 'integer', 'losses' => 'integer', 'points' => 'integer', 'data' => 'array'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(CompetitionRecord::class, 'competition_id');
    }
}
