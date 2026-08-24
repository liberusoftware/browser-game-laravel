<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompetitionMatch extends Model
{
    use HasUuids;

    protected $table = 'browser_game_competition_matches';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['evidence' => 'array'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(CompetitionRecord::class, 'competition_id');
    }
}
