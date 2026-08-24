<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'item_id',
        'quantity',
        'price_per_unit',
        'status',
        'sold_at',
        'buyer_id',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    /** @return BelongsTo<Player, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'buyer_id');
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
