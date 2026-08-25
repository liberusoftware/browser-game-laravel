<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class QuestProgress extends Model
{
    use HasUuids;

    protected $table = 'browser_game_quest_progress';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['progress' => 'array', 'accepted_at' => 'datetime', 'completed_at' => 'datetime', 'reward_claimed_at' => 'datetime', 'completion_count' => 'integer'];
    }
}
