<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class AccountsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_accounts';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'email_verified_at' => 'datetime',
            'birth_year' => 'integer',
            'age_verified' => 'boolean',
            'suspended_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AccountSession::class, 'account_id');
    }

    public function bans(): HasMany
    {
        return $this->hasMany(AccountBan::class, 'account_id');
    }

    public function recoveries(): HasMany
    {
        return $this->hasMany(AccountRecovery::class, 'account_id');
    }

    public function privacy(): HasOne
    {
        return $this->hasOne(AccountPrivacy::class, 'account_id');
    }
}
