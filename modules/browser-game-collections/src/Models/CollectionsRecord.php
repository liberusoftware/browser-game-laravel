<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CollectionsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_collections';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
