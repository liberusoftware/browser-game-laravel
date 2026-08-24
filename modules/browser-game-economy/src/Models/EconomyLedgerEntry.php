<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class EconomyLedgerEntry extends Model
{
    use HasUuids;

    protected $table = 'browser_game_economy_ledger';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_after' => 'integer', 'metadata' => 'array'];
    }
}
