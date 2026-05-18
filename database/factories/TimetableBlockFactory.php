<?php

namespace Database\Factories;

use App\Models\TimetableBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimetableBlockFactory extends Factory
{
    protected $model = TimetableBlock::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->words(2, true),
            'type' => 'study',
            'color' => '#'.ltrim($this->faker->hexColor(), '#'),
            'project_id' => null,
            'days_of_week' => [1, 3, 5],
            'start_time' => '09:00',
            'end_time' => '10:00',
            'active' => true,
            'semester_end' => null,
            'created_at' => now(),
        ];
    }
}
