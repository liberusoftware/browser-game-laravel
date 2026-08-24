<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LiveOpsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_live_ops';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'version' => 'integer'];
    }

    public function claims(): HasMany
    {
        return $this->hasMany(LiveOpsClaim::class, 'live_ops_id');
    }

    public function rollbacks(): HasMany
    {
        return $this->hasMany(LiveOpsRollback::class, 'live_ops_id');
    }
}
