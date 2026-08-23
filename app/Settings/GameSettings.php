<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GameSettings extends Settings
{
    public int $max_player_level;

    public int $starting_level;

    public int $starting_gold;

    public int $starting_experience;

    public bool $player_registration_enabled;

    public int $experience_multiplier;

    public int $gold_multiplier;

    public int $max_inventory_size;

    public int $max_active_quests;

    public bool $pvp_enabled;

    public bool $guild_system_enabled;

    public static function group(): string
    {
        return 'game';
    }
}
