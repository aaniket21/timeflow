<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'daily_hours',
            'title' => $this->faker->words(3, true),
            'target_value' => $this->faker->randomFloat(2, 1, 10),
            'active' => true,
            'created_at' => now(),
        ];
    }
}
