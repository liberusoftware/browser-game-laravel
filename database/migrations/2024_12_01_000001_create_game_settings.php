<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class() extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('game.max_player_level', 100);
        $this->migrator->add('game.starting_level', 1);
        $this->migrator->add('game.starting_gold', 100);
        $this->migrator->add('game.starting_experience', 0);
        $this->migrator->add('game.player_registration_enabled', true);
        $this->migrator->add('game.experience_multiplier', 1);
        $this->migrator->add('game.gold_multiplier', 1);
        $this->migrator->add('game.max_inventory_size', 100);
        $this->migrator->add('game.max_active_quests', 10);
        $this->migrator->add('game.pvp_enabled', false);
        $this->migrator->add('game.guild_system_enabled', true);
    }
};
