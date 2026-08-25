<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompetitionFlag extends Model
{
    protected $table = 'browser_game_competition_flags';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(CompetitionRecord::class, 'competition_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(CompetitionMatch::class, 'match_id');
    }
}
