<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountPrivacy extends Model
{
    protected $table = 'browser_game_account_privacy';

    protected $primaryKey = 'account_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'marketing_consent' => 'boolean',
            'analytics_consent' => 'boolean',
            'deletion_requested_at' => 'datetime',
            'deletion_completed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountsRecord::class, 'account_id');
    }
}
