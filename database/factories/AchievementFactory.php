<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'icon' => 'heroicon-o-trophy',
            'points' => $this->faker->numberBetween(10, 100),
            'requirement_type' => $this->faker->randomElement(['level', 'quests_completed', 'items_collected', 'experience']),
            'requirement_value' => $this->faker->numberBetween(1, 50),
        ];
    }
}
