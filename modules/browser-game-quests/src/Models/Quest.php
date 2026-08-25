<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Quest extends Model
{
    use HasUuids;

    protected $table = 'browser_game_quests';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['objectives' => 'array', 'prerequisites' => 'array', 'branches' => 'array', 'dialogue' => 'array', 'rewards' => 'array', 'repeatable' => 'boolean'];
    }
}
