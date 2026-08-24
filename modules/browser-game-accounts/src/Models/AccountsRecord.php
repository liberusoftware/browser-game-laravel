<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AccountsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_accounts';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
