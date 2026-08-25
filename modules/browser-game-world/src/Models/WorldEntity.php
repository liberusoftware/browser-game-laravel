<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $tenant_id
 * @property string|null $team_id
 * @property string|null $world_id
 * @property string $kind
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property array<string,mixed>|null $coordinates
 * @property string|null $unlock_key
 */
final class WorldEntity extends Model
{
    use HasUuids;

    protected $table = 'browser_game_world_entities';

    protected $fillable = ['tenant_id', 'team_id', 'world_id', 'kind', 'name', 'slug', 'status', 'attributes', 'coordinates', 'unlock_key'];

    protected function casts(): array
    {
        return ['attributes' => 'array', 'coordinates' => 'array'];
    }
}
