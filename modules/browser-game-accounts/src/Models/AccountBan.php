<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountBan extends Model
{
    protected $table = 'browser_game_account_bans';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountsRecord::class, 'account_id');
    }
}
