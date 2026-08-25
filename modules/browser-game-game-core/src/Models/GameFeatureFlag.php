<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class GameFeatureFlag extends Model
{
    use HasUuids;

    protected $table = 'browser_game_feature_flags';

    protected $fillable = ['world_id', 'key', 'enabled', 'rollout_percentage', 'constraints', 'changed_by'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'rollout_percentage' => 'integer', 'constraints' => 'array'];
    }
}
