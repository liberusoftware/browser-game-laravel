<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class WorldUnlock extends Model
{
    use HasUuids;

    protected $table = 'browser_game_world_unlocks';

    protected $fillable = [
        'tenant_id', 'team_id', 'actor_id', 'entity_id', 'unlock_key', 'status',
        'metadata', 'idempotency_key', 'granted_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
