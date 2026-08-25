<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommerceEntitlement extends Model
{
    protected $table = 'browser_game_commerce_entitlements';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'data' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CommerceOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CommerceProduct::class, 'product_id');
    }
}
