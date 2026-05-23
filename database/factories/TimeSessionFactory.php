<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TimeSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeSessionFactory extends Factory
{
    protected $model = TimeSession::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-7 days', 'now');

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'started_at' => $start,
            'ended_at' => null,
            'duration_seconds' => null,
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'is_pomodoro' => false,
            'created_at' => now(),
        ];
    }
}
