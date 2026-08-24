<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommerceOrder extends Model
{
    use HasUuids;

    protected $table = 'browser_game_commerce_orders';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['subtotal' => 'integer', 'total' => 'integer', 'data' => 'array', 'completed_at' => 'datetime'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CommerceOrderLine::class, 'order_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(CommerceEntitlement::class, 'order_id');
    }
}
