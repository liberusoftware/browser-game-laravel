<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\PlayerStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerStatisticFactory extends Factory
{
    protected $model = PlayerStatistic::class;

    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'total_quests_completed' => $this->faker->numberBetween(0, 100),
            'total_items_collected' => $this->faker->numberBetween(0, 200),
            'total_playtime_minutes' => $this->faker->numberBetween(0, 10000),
            'highest_level_achieved' => $this->faker->numberBetween(1, 100),
            'total_experience_earned' => $this->faker->numberBetween(0, 100000),
            'quests_in_progress' => $this->faker->numberBetween(0, 10),
            'achievements_unlocked' => $this->faker->numberBetween(0, 50),
        ];
    }
}
