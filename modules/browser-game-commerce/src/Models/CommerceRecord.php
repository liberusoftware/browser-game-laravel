<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CommerceRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_commerce';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
