<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountSession extends Model
{
    protected $table = 'browser_game_account_sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountsRecord::class, 'account_id');
    }
}
