<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CompetitionRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_competition';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'season' => 'integer'];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CompetitionEntry::class, 'competition_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(CompetitionMatch::class, 'competition_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(CompetitionReward::class, 'competition_id');
    }

    public function flags(): HasMany
    {
        return $this->hasMany(CompetitionFlag::class, 'competition_id');
    }
}
