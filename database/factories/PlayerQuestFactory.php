<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Player_Quest;
use App\Models\Quest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player_Quest>
 */
class PlayerQuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'quest_id' => Quest::factory(),
            'status' => $this->faker->randomElement(['in-progress', 'completed']),
        ];
    }
}
