<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EconomyVendorOffer extends Model
{
    protected $table = 'browser_game_economy_vendor_offers';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'stock' => 'integer', 'max_per_actor' => 'integer', 'data' => 'array'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(EconomyVendor::class, 'vendor_id');
    }
}
