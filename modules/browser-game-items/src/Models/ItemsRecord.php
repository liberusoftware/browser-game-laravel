<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ItemsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_items';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
