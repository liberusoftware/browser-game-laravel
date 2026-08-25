<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Player_Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player_Profile>
 */
class PlayerProfileFactory extends Factory
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
            'avatar_url' => $this->faker->imageUrl(),
            'bio' => $this->faker->paragraph,
        ];
    }
}
