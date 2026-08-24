<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EconomyVendor extends Model
{
    protected $table = 'browser_game_economy_vendors';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(EconomyVendorOffer::class, 'vendor_id');
    }
}
