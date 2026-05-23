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
            'day_of_week' => $this->faker->numberBetween(1, 7),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_recurring' => true,
            'created_at' => now(),
        ];
    }
}
