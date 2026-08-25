<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player_Item>
 */
class PlayerItemFactory extends Factory
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
            'item_id' => Item::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
