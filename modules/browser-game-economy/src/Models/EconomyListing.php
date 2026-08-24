<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Models;

use Illuminate\Database\Eloquent\Model;

final class EconomyListing extends Model
{
    protected $table = 'browser_game_economy_listings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'fee' => 'integer',
            'sold_at' => 'datetime',
            'asset_reference' => 'array',
        ];
    }
}
