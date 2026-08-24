<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $player_id
 * @property string|null $world_id
 * @property string|null $team_id
 * @property string $name
 * @property string $race
 * @property string $class
 * @property string|null $background
 * @property array<string, int> $statistics
 * @property array<string, int> $skills
 * @property int $experience
 * @property int $level
 * @property int $health
 * @property int $max_health
 * @property int $available_skill_points
 * @property int $respec_count
 */
final class GameCharacter extends Model
{
    use HasUuids;

    protected $table = 'browser_game_characters';

    protected $fillable = ['player_id', 'world_id', 'team_id', 'name', 'race', 'class', 'background', 'statistics', 'skills', 'experience', 'level', 'health', 'max_health', 'available_skill_points', 'respec_count'];

    protected function casts(): array
    {
        return ['statistics' => 'array', 'skills' => 'array', 'experience' => 'integer', 'level' => 'integer', 'health' => 'integer', 'max_health' => 'integer', 'available_skill_points' => 'integer', 'respec_count' => 'integer'];
    }
}
