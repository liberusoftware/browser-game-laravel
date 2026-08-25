<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $player_id
 * @property string|null $world_id
 * @property string|null $tenant_id
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
 * @property string|null $last_operation_key
 */
final class GameCharacter extends Model
{
    use HasUuids;

    protected $table = 'browser_game_characters';

    protected $fillable = ['player_id', 'world_id', 'tenant_id', 'team_id', 'name', 'race', 'class', 'background', 'statistics', 'skills', 'experience', 'level', 'health', 'max_health', 'mana', 'max_mana', 'strength', 'defense', 'agility', 'intelligence', 'stat_points', 'available_skill_points', 'respec_count', 'last_operation_key', 'last_battle_at', 'last_action_at'];

    protected function casts(): array
    {
        return ['statistics' => 'array', 'skills' => 'array', 'experience' => 'integer', 'level' => 'integer', 'health' => 'integer', 'max_health' => 'integer', 'mana' => 'integer', 'max_mana' => 'integer', 'strength' => 'integer', 'defense' => 'integer', 'agility' => 'integer', 'intelligence' => 'integer', 'stat_points' => 'integer', 'available_skill_points' => 'integer', 'respec_count' => 'integer', 'last_battle_at' => 'datetime', 'last_action_at' => 'datetime'];
    }
}
