<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Quest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quest>
 */
class QuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'experience_reward' => $this->faker->numberBetween(100, 1000),
            'item_reward_id' => Item::factory()->create()->id,
        ];
    }
}
