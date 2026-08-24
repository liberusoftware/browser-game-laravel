<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommerceProduct extends Model
{
    use HasUuids;

    protected $table = 'browser_game_commerce_products';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer', 'max_per_actor' => 'integer', 'delivery' => 'array', 'data' => 'array'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CommerceOrderLine::class, 'product_id');
    }
}
