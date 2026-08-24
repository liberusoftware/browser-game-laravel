<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountRecovery extends Model
{
    protected $table = 'browser_game_account_recoveries';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountsRecord::class, 'account_id');
    }
}
