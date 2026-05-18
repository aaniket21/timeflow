<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\HabitLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HabitLogFactory extends Factory
{
    protected $model = HabitLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'goal_id' => Goal::factory(),
            'date' => now()->toDateString(),
            'done' => $this->faker->boolean(50),
        ];
    }
}
