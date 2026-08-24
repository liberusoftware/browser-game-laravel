<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** @property string $id @property string $actor_id @property string|null $origin_id @property string $destination_id @property string|null $idempotency_key */
final class WorldTravel extends Model
{
    use HasUuids;

    public $updated_at = null;

    protected $table = 'browser_game_world_travels';

    protected $fillable = ['tenant_id', 'team_id', 'actor_id', 'origin_id', 'destination_id', 'idempotency_key', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
