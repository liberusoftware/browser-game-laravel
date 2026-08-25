<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $tenant_id
 * @property string|null $team_id
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class GameWorld extends Model
{
    use HasUuids;

    protected $table = 'browser_game_worlds';

    protected $fillable = ['tenant_id', 'team_id', 'name', 'slug', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
