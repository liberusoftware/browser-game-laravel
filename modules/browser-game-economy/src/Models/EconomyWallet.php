<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Models;

use Illuminate\Database\Eloquent\Model;

final class EconomyWallet extends Model
{
    protected $table = 'browser_game_economy_wallets';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['balance' => 'integer'];
    }
}
