<?php

namespace Database\Factories;

use App\Models\DailyPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyPlanFactory extends Factory
{
    protected $model = DailyPlan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => now()->toDateString(),
            'tasks' => [
                ['text' => $this->faker->sentence(3), 'done' => false],
                ['text' => $this->faker->sentence(3), 'done' => false],
                ['text' => $this->faker->sentence(3), 'done' => false],
            ],
            'created_at' => now(),
        ];
    }
}
