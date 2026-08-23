<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function seller()
    {
        return $this->belongsTo(Player::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Player::class, 'buyer_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
